<?php

// because there is a jscore function, we might need to do this explicitly
// TODO: this should be offloaded then to avoid this include
if (!class_exists("bfCustomExtension")) { // should be in bfcore for sure
	include_once(dirname(__FILE__)."/"."bfCustomExtension/bfCustomExtension.php_noauto");
}

class bfchildlimitation extends bfCustomExtension {
	// Extension definitions
	static $operatorDetailType = "array";
	static $operatorDetail = array(
		"ext_bfchildlimitation_recomputeAllowedChildren" => array( // piped in: the big hash of currently allowed classes
			"node" => array("any", true)
		),
		"ext_bfchildlimitation_getLastActionLog" => null,
		"ext_getExtraColumnInfo" => null,
	);

	private $actionLog = array(); // will contain a hash that will easily describe the state of options after each action taken
	function __construct() {
		parent::__construct();
	}
	
	function ext_bfchildlimitation_getLastActionLog($operatorParameters, $namedParams, $pipedParam) {
		return($this->actionLog);
	}

	function ext_bfchildlimitation_recomputeAllowedChildren($operatorParameters, $namedParams, $pipedParam) {
		$currentListOfChildren = $pipedParam;
		$nodeObj = $namedParams["node"];

		// make it a bit easier to work with single items
		$groupNames = array(); // we'll use this for turning groups on/off
		$classIdentifiers = array();
		$classToGroupHash = array();
		$groupToClassHash = array();
		foreach ($currentListOfChildren as $key => $groupHash) {
			$groupName = $groupHash["group_name"];
			$groupNames[$groupName] = 1;
			$children = $groupHash["items"];
			$currentListOfChildren[$key]["childIdHash"] = array();
			foreach ($children as $classObj) {
				$classID = $classObj->Identifier;
				$classIdentifiers[$classID] = 1;
				$currentListOfChildren[$key]["childIdHash"][$classID] = 1;
				// add to classToGroup hash
				if (!array_key_exists($classID, $classToGroupHash)) {
					$classToGroupHash[$classID] = array();
				}
				array_push($classToGroupHash[$classID], $groupName);
				// add to groupNameToClass hash
				if (!array_key_exists($groupName, $groupToClassHash)) {
					$groupToClassHash[$groupName] = array();
				}
				array_push($groupToClassHash[$groupName], $classID);
			}
		}
		// process our rules for exclusion and inclusion
		// inputs: 
		//	user info = userId, userGroups, userRoles, etc
		//	node info = nodeId, Subtree (descendant of), pathInfo (in path X), nodeClass, etc
			// USER FIRST

		// get rule Set to apply for this
		$bfChildLimitationIni = eZINI::instance("bfchildlimitation.ini");
		$ruleSetName = $bfChildLimitationIni->variable("ChildAddition", "RuleSet"); // that should yield a simple array of XML files
		
		// process our rules for exclusion
		$ruleProcessor = new ruleProcessor();
		$actionArray = $ruleProcessor->processRuleSet($ruleSetName, $nodeObj);

		// stuff in array will conform to these commands: 
		// turnAllGroupsOff, turnAllGroupsOn,
		// turnGroupsOff(groupName[]), turnGroupsOn(groupName[])
		// removeAllClassesExcept(classId[]), addAllClassesExcept(classId[])
		// removeClasses(classId[]), addClasses(classId[])
		// removeAllClasses(), addAllClasses()
		
		// redo $groupNames, $classIdentifiers first, turn them on and off
		$this->processGroupClassActions($actionArray, $groupNames, $classIdentifiers, $groupToClassHash);

		$newListOfChildren = $this->excludeNonEligibleGroupsClasses($groupNames, $classIdentifiers, $currentListOfChildren);
		return($newListOfChildren);
	}

	// works with our newly modified class/group hashes, returns what they display is expecting - a new simple array of eZContentClass objects
	function excludeNonEligibleGroupsClasses(&$groupNames, &$classIdentifiers, &$currentListOfChildren) {
		$newListOfChildren = array();
		foreach ($currentListOfChildren as $key => $groupHash) {
			$groupName = $groupHash["group_name"];
			if ($groupNames[$groupName] == 1) { // the group is ok, has at least one child
				$newChildArr = array();
				$children = $groupHash["items"];
				foreach ($children as $classObj) {
					$classID = $classObj->Identifier;
					if ($classIdentifiers[$classID] == 1) {
						array_push($newChildArr, $classObj); // yup, the child fits
					}
				}
				$groupHash["items"] = $newChildArr; // replace children array
				$newListOfChildren[$key] = $groupHash; // replace entire groupHash
			}
		}
		return($newListOfChildren);
	}

	/**
	 * Changes groupNames, classIdentifiers hash so that the values are 1 or 0 based on actions in action array
	 * Does a final check and nulls out groups that have no children
	 * @param $actionArr
	 * @param $groupNames
	 * @param $classIdentifiers
	 * @param $groupToClassHash - needed for the final check
	 * @param $currentListOfChildren
	 */
	function processGroupClassActions($actionArr, &$groupNames, &$classIdentifiers, $groupToClassHash) {
		// turnAllGroupsOff, turnAllGroupsOn,
		// turnGroupsOff(groupName[]), turnGroupsOn(groupName[])
		// removeAllClassesExcept(classId[]), addAllClassesExcept(classId[])
		// removeClasses(classId[]), addClasses(classId[])
		// removeAllClasses(), addAllClasses()
		$actionsExpectingAnArrayParam = array("turnGroupsOff", "turnGroupsOn", "removeAllClassesExcept", "addAllClassesExcept", "removeClasses", "addClasses", "removeAllGroupClasses", "addAllGroupClasses", "addAllGroupClassesExcept", "removeAllGroupClassesExcept");
		
		$this->logEasyToReadHashOfCurrentState("INITIAL STATE", $groupNames, $classIdentifiers, $groupToClassHash);
		// first mark all groups and classIdentifiers as on or off based on actions
		foreach ($actionArr as $actionHash) {
			// prep name, values
			$actionName = $actionHash["name"];
			$actionValues = @$actionHash["value"];
			if (in_array($actionName, $actionsExpectingAnArrayParam)) {
				$actionValues = explode(",", $actionValues);
			}
			// now process, based on action type
			switch ($actionName) {
				case "turnAllGroupsOff" : 
					foreach ($groupNames as $groupName => $groupVal) {
						$groupNames[$groupName] = 0;
					}
					break;
				case "turnAllGroupsOn" :
					foreach ($groupNames as $groupName => $groupVal) {
						$groupNames[$groupName] = 1;
					}
					break;
				case "turnGroupsOff" : 
					foreach ($groupNames as $groupName => $groupVal) {
						if (in_array($groupName, $actionValues)) {
							$groupNames[$groupName] = 0;
						}
					}
					break;
				case "turnGroupsOn" : 
					foreach ($groupNames as $groupName => $groupVal) {
						if (in_array($groupName, $actionValues)) {
							$groupNames[$groupName] = 1;
						}
					}
					break;
				case "removeAllClassesExcept" :
					foreach ($classIdentifiers as $classID => $classVal) {
						if (!in_array($classID, $actionValues)) {
							$classIdentifiers[$classID] = 0;
						}
					}
					break;
				case "addAllClassesExcept" :
					foreach ($classIdentifiers as $classID => $classVal) {
						if (!in_array($classID, $actionValues)) {
							$classIdentifiers[$classID] = 1;
						}
					}
					break;
				case "removeAllGroupClasses" :
					foreach ($actionValues as $groupName) {
						if (array_key_exists($groupName, $groupToClassHash)) {
							$classesToHide = $groupToClassHash[$groupName];
							foreach ($classesToHide as $classID) {
								$classIdentifiers[$classID] = 0;
							}
						}
					}
					break;
				case "addAllGroupClasses" :
					foreach ($actionValues as $groupName) {
						if (array_key_exists($groupName, $groupToClassHash)) {
							$classesToAdd = $groupToClassHash[$groupName];
							foreach ($classesToAdd as $classID) {
								$classIdentifiers[$classID] = 1;
							}
						}
					}
					break;
				case "addAllGroupClassesExcept" :
					$classIdsToExclude = @explode(",", $actionHash["classes"]);
					foreach ($actionValues as $groupName) {
						if (array_key_exists($groupName, $groupToClassHash)) {
							$classesToAdd = $groupToClassHash[$groupName];
							foreach ($classesToAdd as $classID) {
								if (!in_array($classID, $classIdsToExclude)) {
									$classIdentifiers[$classID] = 1;
								}
							}
						}
					}
					break;
				case "removeAllGroupClassesExcept" : 
					$classIdsToLeave = @explode(",", $actionHash["classes"]);
					foreach ($actionValues as $groupName) {
						if (array_key_exists($groupName, $groupToClassHash)) {
							$classesToHide = $groupToClassHash[$groupName];
							foreach ($classesToHide as $classID) {
								if (!in_array($classID, $classIdsToLeave)) {
									$classIdentifiers[$classID] = 0;
								}
							}
						}
					}
					break;
				case "removeClasses" : 
					foreach ($classIdentifiers as $classID => $classVal) {
						if (in_array($classID, $actionValues)) {
							$classIdentifiers[$classID] = 0;
						}
					}
					break;
				case "addClasses" :
					foreach ($classIdentifiers as $classID => $classVal) {
						if (in_array($classID, $actionValues)) {
							$classIdentifiers[$classID] = 1;
						}
					}
					break;
				case "removeAllClasses" : 
					foreach ($classIdentifiers as $classID => $classVal) {
						$classIdentifiers[$classID] = 0;
					}
					break;
				case "addAllClasses" :
					foreach ($classIdentifiers as $classID => $classVal) {
						$classIdentifiers[$classID] = 1;
					}
					break;
			}
			$noteHash = array(
				"file" => array_pop(explode("/",$actionHash["fromFile"])),
				"rule" => $actionHash["fromRule"],
				"action" => $actionName,
				"value" => $actionValues
			);
			if (array_key_exists("classes", $actionHash)) {
				$noteHash["classes"] = $actionHash["classes"];
			}
			$this->logEasyToReadHashOfCurrentState($noteHash, $groupNames, $classIdentifiers, $groupToClassHash);
		}

//		print_r($this->actionLog);
		// now take the initial array $groupToClassHash, reconcile it with remaining groups and classes
		foreach ($groupNames as $groupName => $groupVal) {
			if ($groupVal == 1) {
				$hasClasses = false;
				foreach ($classIdentifiers as $classID => $classVal) {
					if ($classVal == 1 && in_array($classID, $groupToClassHash[$groupName])) {
						$hasClasses=true;
						break;
					} 
				}
				if (!$hasClasses) { // no classes, need to take the entire group out
					$groupNames[$groupName] = 0;
				}
			}
		}
	}

	// no modification, it's a read-only method, but references should speed it up
	private function logEasyToReadHashOfCurrentState($comingFrom, &$groupNames, &$classIdentifiers, &$groupToClassHash) {
		$cleanedUpGroupClassHash = $this->getCleanedUpGroupClassHash($groupNames, $classIdentifiers, $groupToClassHash);

		array_push($this->actionLog, array(
			"note" => $comingFrom,
			"remainingGroupsClasses" => $cleanedUpGroupClassHash
		));
	}

	private function getCleanedUpGroupClassHash(&$groupNames, &$classIdentifiers, $groupToClassHash) {
		foreach ($groupNames as $groupName => $isActive) {
			if ($isActive == 0) {
				unset ($groupToClassHash[$groupName]);
			} else { // go through its children, adjust active ones
				foreach ($groupToClassHash[$groupName] as $key => $classIdentifier) {
					if ($classIdentifiers[$classIdentifier] == 0) {
						unset ($groupToClassHash[$groupName][$key]);
					}
				}
				// see if any are left
				if (sizeof($groupToClassHash[$groupName]) == 0) {
					unset ($groupToClassHash[$groupName]);
				}
			}

		}
		return($groupToClassHash);
	}

	/**
	 * 
	 * Copied out of ezjsserverfunctionsnode.php, modified to exclude children - used through ezjsnode
	 * @param $args
	 */
	function subTree($args) {
        $parentNodeID = isset( $args[0] ) ? $args[0] : null;
        $limit = isset( $args[1] ) ? $args[1] : 25;
        $offset = isset( $args[2] ) ? $args[2] : 0;
        $sort = isset( $args[3] ) ? self::sortMap( $args[3] ) : 'published';
        $order = isset( $args[4] ) ? $args[4] : false;
        $objectNameFilter = isset( $args[5] ) ? $args[5] : '';

        if ( !$parentNodeID )
        {
            throw new ezcBaseFunctionalityNotSupportedException( 'Fetch node list', 'Parent node id is not valid' );
        }

        $node = eZContentObjectTreeNode::fetch( $parentNodeID );
        if ( !$node instanceOf eZContentObjectTreeNode )
        {
            throw new ezcBaseFunctionalityNotSupportedException( 'Fetch node list', "Parent node '$parentNodeID' is not valid" );
        }

        $params = array( 'Depth' => 1,
                         'Limit' => $limit,
                         'Offset' => $offset,
                         'SortBy' => array( array( $sort, $order ) ),
                         'DepthOperator' => 'eq',
                         'ObjectNameFilter' => $objectNameFilter,
                         'AsObject' => true );
        
        // need to get our extra columns... if you're sorting by it, need to reset a few things
        $childLimitationObj = new bfchildlimitation();
        $extraColumns = $childLimitationObj->ext_getExtraColumnInfo(null, null, null);
		if (array_key_exists($sort, $extraColumns)) {
			$useCustomSorting = true;
			unset($params["Limit"]);
			unset($params["Offset"]);
			$params["SortBy"][0][0] = "published";
		}

        // get extra params, if you have some
		$bfChildLimitationIni = eZINI::instance("bfchildlimitation.ini");
		$childLimitationArr = $bfChildLimitationIni->variable("ChildListing", "ExcludeTypes");
		if (sizeof($childLimitationArr) > 0) {
			$params["ClassFilterType"] = "exclude";
			$params["ClassFilterArray"] = $childLimitationArr;
		}
        
       	// fetch nodes and total node count
        $count = $node->subTreeCount( $params );
        if ( $count )
        {
            $nodeArray = $node->subTree( $params );
        }
        else
        {
            $nodeArray = false;
        }

        // generate json response from node list
        if ( $nodeArray )
        {
            $list = ezjscAjaxContent::nodeEncode( $nodeArray, array( 'formatDate' => 'shortdatetime',
                                                                     'fetchThumbPreview' => true,
                                                                     'fetchSection' => true,
                                                                     'fetchCreator' => true,
                                                                     'fetchClassIcon' => true ), 'raw' );
		}
        else
        {
            $list = array();
        }
		// prepare nodeObj's, no need to repull for each item
		$nodeObjs = array();
		foreach ($nodeArray as $nodeObj) {
			$nodeObjs[$nodeObj->NodeID] = $nodeObj;
		}
		// ADD EXTRA INFO FOR COLUMNS (SEE README-CUSTOMATTRIBUTECOLUMNS)
        for ($i=0; $i<sizeof($list); $i++) {
        	$mainNodeId = $list[$i]["main_node_id"];
        	foreach ($extraColumns as $columnId => $extraColumnHash) {
	        	$list[$i][$columnId] = $childLimitationObj->getExtraColumnData($list[$i], $nodeObjs[$mainNodeId], $columnId, $extraColumnHash);
        	}
        }
        
        // need to resort the list, offset and trim it
        if ($useCustomSorting) {
        	$keyedSortableList = array();
        	foreach ($list as $listItem) {
        		$keyedSortableList[$listItem[$sort]."_".$listItem["main_node_id"]] = $listItem;
        	}
        	if ($order) {
	        	ksort($keyedSortableList);
        	} else {
        		krsort($keyedSortableList);
        	}
        	$sortedList = array_values($keyedSortableList);
        	// now offset and limit
        	if ($offset >= sizeof($sortedList)) {
        		$offset = 0;
        	}
        	if ($offset + $limit > sizeof($sortedList)) {
        		$limit = sizeof($sortedList) - $offset;
        	}
        	$sortedList = array_slice($sortedList, $offset, $limit);
        	$list = $sortedList;
        }
        
        return array( 'parent_node_id' => $parentNodeID,
                      'count' => count( $nodeArray ),
                      'total_count' => (int)$count,
                      'list' => $list,
                      'limit' => $limit,
                      'offset' => $offset,
                      'sort' => $sort,
                      'order' => $order );
	}
	
	protected static function sortMap( $sort )
    {
        switch ( $sort )
        {
            case 'modified_date':
                $sortKey = 'modified';
                break;
            case 'published_date':
                $sortKey = 'published';
                break;
            default:
                $sortKey = $sort;
        }

        return $sortKey;
    }
	
    function getExtraColumnData(&$nodeArr, $nodeObj, $columnId, &$extraColumnHash) {
    	if (!array_key_exists("ValueGenerationStaticMethod", $extraColumnHash)) {
    		// try to get that attribute, run ToString() on it
			$value = "";
    		$dataMap = $nodeArr["dataMap"];
			if (array_key_exists($columnId, $dataMap)) {
				$value = $dataMap[$columnId]->ToString();
			}
    		return($value);
    	}

    	// ok, we do have a static method
    	$staticClassMethod = $extraColumnHash["ValueGenerationStaticMethod"];
    	list($class, $method) = explode("::", $staticClassMethod);
    	$paramArr = array(
    		"nodeArr" => $nodeArr,
    		"nodeObj" => $nodeObj,
    		"columnId" => $columnId,
    		"columnHash" => $extraColumnHash
    	);
    	$retVal = call_user_func(array($class, $method), $paramArr);
    	return($retVal);
    }
    /**
     * Get a single hash of extra column information
     * @param $operatorParameters
     * @param $namedParams
     * @param $pipedParam
     * @return $columnInfoHash
     */
    function ext_getExtraColumnInfo($operatorParameters, $namedParams, $pipedParam) {
		$bfChildLimitationIni = eZINI::instance("bfchildlimitation.ini");
		$columns = $bfChildLimitationIni->variable("ExtraChildlistColumns", "Columns"); // that should yield a simple array of names
		$retHash = array();
		foreach ($columns as $columnId) {
			$blockName = "ExtraChildlistColumn_".$columnId;
			if ($bfChildLimitationIni->hasGroup($blockName)) {
				$childDefHash = array();
				if ($bfChildLimitationIni->hasVariable($blockName, "Label")) {
					$childDefHash["Label"] = $bfChildLimitationIni->variable($blockName, "Label");
				} else {
					$childDefHash["Label"] = $columnId;
				}
				if ($bfChildLimitationIni->hasVariable($blockName, "ValueGenerationStaticMethod")) {
					$childDefHash["ValueGenerationStaticMethod"] = $bfChildLimitationIni->variable($blockName, "ValueGenerationStaticMethod");
				}
				if ($bfChildLimitationIni->hasVariable($blockName, "IsSortable")) {
					$childDefHash["IsSortable"] = $bfChildLimitationIni->variable($blockName, "IsSortable") == "true";
				} else {
					$childDefHash["IsSortable"] = true;
				}
				if ($bfChildLimitationIni->hasVariable($blockName, "IsResizable")) {
					$childDefHash["IsResizable"] = $bfChildLimitationIni->variable($blockName, "IsResizable") == "true";
				} else {
					$childDefHash["IsResizable"] = true;
				}
				$retHash[$columnId] = $childDefHash;
			}
		}
		return($retHash);
    }
}
?>