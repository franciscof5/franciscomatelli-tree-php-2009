<?php

/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
*
 ********************************************************************************/


require_once('include/database/PearDatabase.php');
require_once('include/database/Postgres8.php');
require_once('include/ComboUtil.php'); //new
require_once('include/utils/CommonUtils.php'); //new
	
$column_array=array('accountid','contact_id','product_id','campaignid','quoteid','vendorid','potentialid','salesorderid','vendor_id','contactid','handler');
$table_col_array=array('vtiger_account.accountname','vtiger_contactdetails.firstname,vtiger_contactdetails.lastname','vtiger_products.productname','vtiger_campaign.campaignname','vtiger_quotes.subject','vtiger_vendor.vendorname','vtiger_potential.potentialname','vtiger_salesorder.subject','vtiger_vendor.vendorname','vtiger_contactdetails.firstname,vtiger_contactdetails.lastname','vtiger_users.user_name');

/**This function is used to get the list view header values in a list view during search
*Param $focus - module object
*Param $module - module name
*Param $sort_qry - sort by value
*Param $sorder - sorting order (asc/desc)
*Param $order_by - order by
*Param $relatedlist - flag to check whether the header is for listvie or related list
*Param $oCv - Custom view object
*Returns the listview header values in an array
*/

function getSearchListHeaderValues($focus, $module,$sort_qry='',$sorder='',$order_by='',$relatedlist='',$oCv='')
{
	global $log;
	$log->debug("Entering getSearchListHeaderValues(".get_class($focus).",". $module.",".$sort_qry.",".$sorder.",".$order_by.",".$relatedlist.",".get_class($oCv).") method ...");
        global $adb;
        global $theme;
        global $app_strings;
        global $mod_strings,$current_user;

        $arrow='';
        $qry = getURLstring($focus);
        $theme_path="themes/".$theme."/";
        $image_path=$theme_path."images/";
        $search_header = Array();

        //Get the vtiger_tabid of the module
        //require_once('include/utils/UserInfoUtil.php')
        $tabid = getTabid($module);
        //added for vtiger_customview 27/5
        if($oCv)
        {
                if(isset($oCv->list_fields))
		{
                        $focus->list_fields = $oCv->list_fields;
                }
        }
	//Added to reduce the no. of queries logging for non-admin vtiger_users -- by Minnie-start
	$field_list ='(';
	$j=0;	
	require('user_privileges/user_privileges_'.$current_user->id.'.php');
	foreach($focus->list_fields as $name=>$tableinfo)
	{
		$fieldname = $focus->list_fields_name[$name];
		if($oCv)
		{
			if(isset($oCv->list_fields_name))
			{
				$fieldname = $oCv->list_fields_name[$name];
			}
		}
		if($j != 0)
		{
			$field_list .= ', ';
		}
		if($fieldname == "accountname" && $module !="Accounts")
			$fieldname = "account_id";
		if($fieldname == "lastname" && $module !="Leads" && $module !="Contacts")
		{
			$fieldname = "contact_id";
		}
		$field_list .= "'".$fieldname."'";
		$j++;
	}
	$field_list .=')';
	//Getting the Entries from Profile2 vtiger_field vtiger_table
	if($is_admin == false)
	{
		$profileList = getCurrentUserProfileList();
		//changed to get vtiger_field.fieldname
		$query  = "SELECT vtiger_profile2field.*,vtiger_field.fieldname FROM vtiger_field INNER JOIN vtiger_profile2field ON vtiger_profile2field.fieldid=vtiger_field.fieldid INNER JOIN vtiger_def_org_field ON vtiger_def_org_field.fieldid=vtiger_field.fieldid WHERE vtiger_field.tabid=".$tabid." AND vtiger_profile2field.visible=0 AND vtiger_def_org_field.visible=0 AND vtiger_profile2field.profileid IN ".$profileList." AND vtiger_field.fieldname IN ".$field_list." GROUP BY vtiger_field.fieldid";
 		if( $adb->dbType == "pgsql")
 		    $query = fixPostgresQuery( $query, $log, 0);
		$result = $adb->query($query);
		$field=Array();
		for($k=0;$k < $adb->num_rows($result);$k++)
		{
			$field[]=$adb->query_result($result,$k,"fieldname");
		}
	}

        //modified for vtiger_customview 27/5 - $app_strings change to $mod_strings
        foreach($focus->list_fields as $name=>$tableinfo)
        {
                //$fieldname = $focus->list_fields_name[$name];  //commented for vtiger_customview 27/5
                //added for vtiger_customview 27/5
                if($oCv)
                {
                        if(isset($oCv->list_fields_name))
                        {
				if( $oCv->list_fields_name[$name] == '')
					$fieldname = 'crmid';
				else
					$fieldname = $oCv->list_fields_name[$name];
                        }else
                        {
				if( $focus->list_fields_name[$name] == '')
					$fieldname = 'crmid';
				else
					$fieldname = $focus->list_fields_name[$name];
					
                        }
			if($fieldname == "lastname" && $module !="Leads" && $module !="Contacts")
				$fieldname = "contact_id";
			if($fieldname == "accountname" && $module !="Accounts")
				$fieldname = "account_id";
                }
		else
                {
			if( $focus->list_fields_name[$name] == '')
				$fieldname = 'crmid';
			else
				$fieldname = $focus->list_fields_name[$name];

			if($fieldname == "lastname" && $module !="Leads" && $module !="Contacts")
                                $fieldname = "contact_id";
                }
                if($is_admin == true || $profileGlobalPermission[1] == 0 || $profileGlobalPermission[2] ==0 || in_array($fieldname,$field))
		{
			if($fieldname!='parent_id')
			{
				$fld_name=$fieldname;
				if($fieldname == 'contact_id' && $module !="Contacts") 
				$name = $app_strings['LBL_CONTACT_LAST_NAME'];
				elseif($fieldname == 'contact_id' && $module =="Contacts") 
				$name = $mod_strings['Reports To']." - ".$mod_strings['LBL_LIST_LAST_NAME'];
				//assign the translated string
				$search_header[$fld_name] = getTranslatedString($name);
			}
                }
        }
	$log->debug("Exiting getSearchListHeaderValues method ...");	
        return $search_header;

}

/**This function is used to get the where condition for search listview query along with url_string
*Param $module - module name
*Returns the where conditions and url_string values in string format
*/

function Search($module)
{
	global $log,$default_charset;
        $log->debug("Entering Search(".$module.") method ...");
	$url_string='';	
	if(isset($_REQUEST['search_field']) && $_REQUEST['search_field'] !="")
        {
                $search_column=$_REQUEST['search_field'];
        }
        if(isset($_REQUEST['search_text']) && $_REQUEST['search_text']!="")
        {
		// search other characters like "|, ?, ?" by jagi

		$search_string = $_REQUEST['search_text'];
		
		$stringConvert = function_exists(iconv) ? @iconv("UTF-8",$default_charset,$search_string) : $search_string;

		$search_string=addslashes(ltrim(rtrim($stringConvert)));

		// $search_string=addslashes(ltrim(rtrim($_REQUEST['search_text'])));

        }
        if(isset($_REQUEST['searchtype']) && $_REQUEST['searchtype']!="")
        {


                $search_type=$_REQUEST['searchtype'];

                if($search_type == "BasicSearch")
                {
                        $where=BasicSearch($module,$search_column,$search_string);
                }
                else if ($search_type == "AdvanceSearch")
                {
                }
                else //Global Search
                {
                }
		$url_string = "&search_field=".$search_column."&search_text=".$search_string."&searchtype=BasicSearch";
		if(isset($_REQUEST['type']) && $_REQUEST['type'] != '')
			$url_string .= "&type=".$_REQUEST['type'];
		return $where."#@@#".$url_string;
		$log->debug("Exiting Search method ...");
        }

}

/**This function is used to get user_id's for a given user_name during search
*Param $table_name - vtiger_tablename
*Param $column_name - columnname
*Param $search_string - searchstring value (username)
*Returns the where conditions for list query in string format
*/

function get_usersid($table_name,$column_name,$search_string)
{

	global $log;
        $log->debug("Entering get_usersid(".$table_name.",".$column_name.",".$search_string.") method ...");
	global $adb;
	$user_qry="select distinct(vtiger_users.id)from vtiger_users inner join vtiger_crmentity on vtiger_crmentity.smownerid=vtiger_users.id where vtiger_users.user_name like '%".$search_string."%' ";
	$user_result=$adb->query($user_qry);
	$noofuser_rows=$adb->num_rows($user_result);
	$x=$noofuser_rows-1;
	if($noofuser_rows!=0)
	{
		$where="(";
		for($i=0;$i<$noofuser_rows;$i++)
		{
			$user_id=$adb->query_result($user_result,$i,'id');
			$where .= "$table_name.$column_name =".$user_id;
			if($i != $x)
			{
				$where .= " or ";
			}
		}
		$where.=" or vtiger_groups.groupname like '%".$search_string."%')";
	}
	else
	{
		//$where="$table_name.$column_name =''";
		$where="vtiger_groups.groupname like '%".$search_string."%' ";
	}	
	$log->debug("Exiting get_usersid method ...");
	return $where;	
}

/**This function is used to get where conditions for a given vtiger_accountid or contactid during search for their respective names
*Param $column_name - columnname
*Param $search_string - searchstring value (username)
*Returns the where conditions for list query in string format
*/


function getValuesforColumns($column_name,$search_string,$criteria='cts')
{
	global $log;
	$log->debug("Entering getValuesforColumns(".$column_name.",".$search_string.") method ...");
	global $column_array,$table_col_array;
	for($i=0; $i<count($column_array);$i++)
	{
		if($column_name == $column_array[$i])
		{
			$val=$table_col_array[$i];
			$explode_column=explode(",",$val);
			$x=count($explode_column);	
			if($x == 1 )
			{
				$where=getSearch_criteria($criteria,$search_string,$val);
			}
			else 
			{
				$where="(";
				for($j=0;$j<count($explode_column);$j++)
				{
					$where .=getSearch_criteria($criteria,$search_string,$explode_column[$j]);
					if($j != $x-1)
					{
						if($criteria == 'dcts' || $criteria == 'isn')
							$where .= " and ";
						else
							$where .= " or ";
					}
				}
				$where.=")";
			}
			break 1;
		}
	}
	$log->debug("Exiting getValuesforColumns method ...");
	return $where;
}

/**This function is used to get where conditions in Basic Search
*Param $module - module name
*Param $search_field - columnname/field name in which the string has be searched
*Param $search_string - searchstring value (username)
*Returns the where conditions for list query in string format
*/

function BasicSearch($module,$search_field,$search_string)
{
	 global $log;
         $log->debug("Entering BasicSearch(".$module.",".$search_field.",".$search_string.") method ...");
	global $adb;
	$search_string = ltrim(rtrim($search_string));
	global $column_array,$table_col_array;
	if($search_field =='crmid')
	{
		$column_name='crmid';
		$table_name='vtiger_crmentity';
		$where="$table_name.$column_name like '%".$search_string."%'";
	}else
	{	
		//Check added for tickets by accounts/contacts in dashboard
		$search_field_first = $search_field;
		if($module=='HelpDesk' && ($search_field == 'contactid' || $search_field == 'account_id'))
		{
			$search_field = "parent_id";
		}
		//Check ends
		
		//Added to search contact name by lastname
		if(($module == "Calendar" || $module == "Invoice" || $module == "Notes" || $module == "SalesOrder" || $module== "PurchaseOrder") && ($search_field == "contact_id"))
	       {
	                 $module = 'Contacts';
	                 $search_field = 'lastname';
	       }
		if($search_field == "accountname" && $module != "Accounts")
			$search_field = "account_id";
		$qry="select vtiger_field.columnname,tablename from vtiger_tab inner join vtiger_field on vtiger_field.tabid=vtiger_tab.tabid where vtiger_tab.name='".$module."' and (fieldname='".$search_field."' or columnname='".$search_field."')";
		$result = $adb->query($qry);
		$noofrows = $adb->num_rows($result);
		if($noofrows!=0)
		{
			$column_name=$adb->query_result($result,0,'columnname');	
			
			//Check added for tickets by accounts/contacts in dashboard
			if ($column_name == 'parent_id')
		        {
				if ($search_field_first	== 'account_id') $search_field_first = 'accountid';
				if ($search_field_first	== 'contactid') $search_field_first = 'contact_id';
				$column_name = $search_field_first;
			}
				
			//Check ends
			$table_name=$adb->query_result($result,0,'tablename');
			$uitype=getUItype($module,$column_name);
			
			//Added for Member of search in Accounts
			if($column_name == "parentid" && $module == "Accounts")
			{
				$table_name = "vtiger_account2";
				$column_name = "accountname";
			}
			if($column_name == "reportsto" && $module == "Contacts")
			{
				$table_name = "vtiger_contactdetails2";
				$column_name = "lastname";
			}
			if($column_name == "inventorymanager" && $module = "Quotes")
			{
				$table_name = "vtiger_usersQuotes";
				$column_name = "user_name";
			}
			//Added to support user date format in basic search	
			if($uitype == 5 || $uitype == 6 || $uitype == 23 || $uitype == 70)
			{
				list($sdate,$stime) = split(" ",$search_string);
				if($stime !='')
					$search_string = getDBInsertDateValue($sdate)." ".$stime;
				else
					$search_string = getDBInsertDateValue($sdate);
			}
			// Added to fix errors while searching check box type fields(like product active. ie. they store 0 or 1. we search them as yes or no) in basic search.
			if ($uitype == 56)
			{
				if(stristr($search_string,'yes'))
					$where="$table_name.$column_name = '1'";
				elseif(stristr($search_string,'no'))
					$where="$table_name.$column_name = '0'";
				else
					$where="$table_name.$column_name = '$search_string'";

			}
			elseif($table_name == "vtiger_crmentity" && $column_name == "smownerid")
			{
				$where = get_usersid($table_name,$column_name,$search_string);
			}
			elseif($table_name == "vtiger_activity" && $column_name == "status")
			{
				$where="($table_name.$column_name like '%".$search_string."%' or vtiger_activity.eventstatus like '%".$search_string."%')";
			}
			else if(in_array($column_name,$column_array))
			{
				$where = getValuesforColumns($column_name,$search_string);
			}
			else
			{
				$where="$table_name.$column_name like '%".$search_string."%'";
			}
		}
	}
	if(stristr($where,"like '%%'"))
	{
		$where_cond0=str_replace("like '%%'","like ''",$where);
		$where_cond1=str_replace("like '%%'","is NULL",$where);
		if($module == "Calendar")
			$where = "(".$where_cond0." and ".$where_cond1.")";
		else
			$where = "(".$where_cond0." or ".$where_cond1.")";
	}
	if($_REQUEST['type'] == 'entchar')
	{
		$search = array('Un Assigned','%','like');
		$replace = array('','','=');
		$where= str_replace($search,$replace,$where);
	}
	if($_REQUEST['type'] == 'alpbt')
	{
	        $where = str_replace_once("%", "", $where);
	}
	$log->debug("Exiting BasicSearch method ...");
	return $where;
}

/**This function is used to get where conditions in Advance Search
*Param $module - module name
*Returns the where conditions for list query in string format
*/

function getAdvSearchfields($module)
{
	global $log;
        $log->debug("Entering getAdvSearchfields(".$module.") method ...");
	global $adb;
	global $current_user;	
	global $mod_strings,$app_strings;
	require('user_privileges/user_privileges_'.$current_user->id.'.php');

	$tabid = getTabid($module);
        if($tabid==9)
                $tabid="9,16";				

	if($is_admin == true || $profileGlobalPermission[1] == 0 || $profileGlobalPermission[2] == 0)
	{
		$sql = "select * from vtiger_field ";
		$sql.= " where vtiger_field.tabid in(".$tabid.") and";
		$sql.= " vtiger_field.displaytype in (1,2,3)";
		if($tabid == 13 || $tabid == 15)
		{
			$sql.= " and vtiger_field.fieldlabel != 'Add Comment'";
		}
		if($tabid == 14)
		{
			$sql.= " and vtiger_field.fieldlabel != 'Product Image'";
		}
		if($tabid == 9 || $tabid==16)
		{
			$sql.= " and vtiger_field.fieldname not in('notime','duration_minutes','duration_hours')";
		}
		if($tabid == 4)
		{
			$sql.= " and vtiger_field.fieldlabel != 'Contact Image'";
		}
		if($tabid == 13 || $tabid == 10)
		{
			$sql.= " and vtiger_field.fieldlabel != 'Attachment'";
		}
		$sql.= "group by vtiger_field.fieldlabel order by block,sequence";
	}
	else
	{
		$profileList = getCurrentUserProfileList();
		$sql = "select * from vtiger_field inner join vtiger_profile2field on vtiger_profile2field.fieldid=vtiger_field.fieldid inner join vtiger_def_org_field on vtiger_def_org_field.fieldid=vtiger_field.fieldid ";
		$sql.= " where vtiger_field.tabid in(".$tabid.") and";
		$sql.= " vtiger_field.displaytype in (1,2,3) and vtiger_profile2field.visible=0";
		$sql.= " and vtiger_def_org_field.visible=0  and vtiger_profile2field.profileid in ".$profileList;
		if($tabid == 13 || $tabid == 15)
		{
			$sql.= " and vtiger_field.fieldlabel != 'Add Comment'";
		}
		if($tabid == 14)
		{
			$sql.= " and vtiger_field.fieldlabel != 'Product Image'";
		}
		if($tabid == 9 || $tabid==16)
		{
			$sql.= " and vtiger_field.fieldname not in('notime','duration_minutes','duration_hours')";
		}
		if($tabid == 4)
		{
			$sql.= " and vtiger_field.fieldlabel != 'Contact Image'";
		}
		if($tabid == 13 || $tabid == 10)
		{
			$sql.= " and vtiger_field.fieldlabel != 'Attachment'";
		}
		$sql .= " group by vtiger_field.fieldlabel order by block,sequence";

	}

	$result = $adb->query($sql);
	$noofrows = $adb->num_rows($result);
	$block = '';
	$select_flag = '';

	for($i=0; $i<$noofrows; $i++)
	{
		$fieldtablename = $adb->query_result($result,$i,"tablename");
		$fieldcolname = $adb->query_result($result,$i,"columnname");
		$block = $adb->query_result($result,$i,"block");
		$fieldtype = $adb->query_result($result,$i,"typeofdata");
		$fieldtype = explode("~",$fieldtype);
		$fieldtypeofdata = $fieldtype[0];	
		if($fieldcolname == 'account_id' || $fieldcolname == 'accountid' || $fieldcolname == 'product_id' || $fieldcolname == 'vendor_id' || $fieldcolname == 'contact_id' || $fieldcolname == 'contactid' || $fieldcolname == 'vendorid' || $fieldcolname == 'potentialid' || $fieldcolname == 'salesorderid' || $fieldcolname == 'quoteid' || $fieldcolname == 'parentid' || $fieldcolname == "recurringtype" || $fieldcolname == "campaignid" || $fieldcolname == "inventorymanager" ||  $fieldcolname == "handler")
			$fieldtypeofdata = "V";
		if($fieldcolname == "discontinued" || $fieldcolname == "active")
			$fieldtypeofdata = "C";
		$fieldlabel = $mod_strings[$adb->query_result($result,$i,"fieldlabel")];

		// Added to display customfield label in search options
		if($fieldlabel == "")
			$fieldlabel = $adb->query_result($result,$i,"fieldlabel");
					
		if($fieldlabel == "Related To")
		{
			$fieldlabel = "Related to";
		}
		if($fieldlabel == "Start Date & Time")
		{
			$fieldlabel = "Start Date";
			if($module == 'Activities' && $block == 19)
				$module_columnlist['vtiger_activity:time_start::Activities_Start Time:I'] = 'Start Time';

		}
		$fieldlabel1 = str_replace(" ","_",$fieldlabel);
		//Check added to search the lists by Inventory manager
                if($fieldtablename == 'vtiger_quotes' && $fieldcolname == 'inventorymanager')
                {
                        $fieldtablename = 'vtiger_usersQuotes';
                        $fieldcolname = 'user_name';
                }
		if($fieldtablename == 'vtiger_contactdetails' && $fieldcolname == 'reportsto')
                {
                        $fieldtablename = 'vtiger_contactdetails2';
                        $fieldcolname = 'lastname';
                }
		if($fieldlabel != 'Related to')
		{
			if ($i==0)
				$select_flag = "selected";

			if($fieldlabel == "Product Code")
				$OPTION_SET .= "<option value=\'".$fieldtablename.".".$fieldcolname."::::".$fieldtypeofdata."\'".$select_flag.">".$mod_strings[$fieldlabel]."</option>";
			if($fieldlabel == "Reports To")
				$OPTION_SET .= "<option value=\'".$fieldtablename.".".$fieldcolname."::::".$fieldtypeofdata."\'".$select_flag.">".$mod_strings[$fieldlabel]." - ".$mod_strings['LBL_LIST_LAST_NAME']."</option>";
			elseif($fieldcolname == "contactid" || $fieldcolname == "contact_id")
			{
				$OPTION_SET .= "<option value=\'vtiger_contactdetails.lastname::::".$fieldtypeofdata."\' ".$select_flag.">".$app_strings['LBL_CONTACT_LAST_NAME']."</option>";
				$OPTION_SET .= "<option value=\'vtiger_contactdetails.firstname::::".$fieldtypeofdata."\'>".$app_strings['LBL_CONTACT_FIRST_NAME']."</option>";
			}
			elseif($fieldcolname == "campaignid")
				$OPTION_SET .= "<option value=\'vtiger_campaign.campaignname::::".$fieldtypeofdata."\' ".$select_flag.">".$mod_strings[$fieldlabel]."</option>";
			else
				$OPTION_SET .= "<option value=\'".$fieldtablename.".".$fieldcolname."::::".$fieldtypeofdata."\' ".$select_flag.">".$fieldlabel."</option>";
		}
	}
	//Added to include Ticket ID in HelpDesk advance search
	if($module == 'HelpDesk')
	{
		$OPTION_SET .= "<option value=\'vtiger_crmentity.crmid::::".$fieldtypeofdata."\'>".$mod_strings['Ticket ID']."</option>";
	}
	//Added to include activity type in activity advance search
	if($module == 'Activities')
	{
		$OPTION_SET .= "<option value=\'vtiger_activity.activitytype::::".$fieldtypeofdata."\'>".$mod_strings['Activity Type']."</option>";
	}
	$log->debug("Exiting getAdvSearchfields method ...");
	return $OPTION_SET;
}

/**This function is returns the search criteria options for Advance Search
*takes no parameter
*Returns the criteria option in html format
*/

function getcriteria_options()
{
	global $log,$app_strings;
	$log->debug("Entering getcriteria_options() method ...");
	$CRIT_OPT = "<option value=\'cts\'>".$app_strings['contains']."</option><option value=\'dcts\'>".$app_strings['does_not_contains']."</option><option value=\'is\'>".$app_strings['is']."</option><option value=\'isn\'>".$app_strings['is_not']."</option><option value=\'bwt\'>".$app_strings['begins_with']."</option><option value=\'ewt\'>".$app_strings['ends_with']."</option><option value=\'grt\'>".$app_strings['greater_than']."</option><option value=\'lst\'>".$app_strings['less_than']."</option><option value=\'grteq\'>".$app_strings['greater_or_equal']."</option><option value=\'lsteq\'>".$app_strings['less_or_equal']."</option>";
	$log->debug("Exiting getcriteria_options method ...");
	return $CRIT_OPT;
}

/**This function is returns the where conditions for each search criteria option in Advance Search
*Param $criteria - search criteria option
*Param $searchstring - search string
*Param $searchfield - vtiger_fieldname to be search for 
*Returns the search criteria option (where condition) to be added in list query
*/

function getSearch_criteria($criteria,$searchstring,$searchfield)
{
	global $log;
	$log->debug("Entering getSearch_criteria(".$criteria.",".$searchstring.",".$searchfield.") method ...");
	$searchstring = ltrim(rtrim($searchstring));
	if(($searchfield != "vtiger_troubletickets.update_log") && ($searchfield == "vtiger_crmentity.modifiedtime" || $searchfield == "vtiger_crmentity.createdtime" || stristr($searchfield,'date')))
	{
		list($sdate,$stime) = split(" ",$searchstring);
		if($stime !='')
			$searchstring = getDBInsertDateValue($sdate)." ".$stime;
		else
			$searchstring = getDBInsertDateValue($sdate);
	}
	if($searchfield == "vtiger_account.parentid")
		$searchfield = "vtiger_account2.accountname";
	$where_string = '';
	switch($criteria)
	{
		case 'cts':
			$where_string = $searchfield." like '%".$searchstring."%' ";
			if($searchstring == NULL)
			{
					$where_string = "(".$searchfield." like '' or ".$searchfield." is NULL)";
			}
			break;
		
		case 'dcts':
			if($searchfield == "vtiger_users.user_name" || $searchfield =="vtiger_groups.groupname")	
				$where_string = "(".$searchfield." not like '%".$searchstring."%')";
			else
				$where_string = "(".$searchfield." not like '%".$searchstring."%' or ".$searchfield." is null)";
			if($searchstring == NULL)
			$where_string = "(".$searchfield." not like '' or ".$searchfield." is not NULL)";
			break;
			
		case 'is':
			$where_string = $searchfield." = '".$searchstring."' ";
			if($searchstring == NULL)
			$where_string = "(".$searchfield." is NULL or ".$searchfield." = '')";
			break;
			
		case 'isn':
			if($searchfield == "vtiger_users.user_name" || $searchfield =="vtiger_groups.groupname")
				$where_string = "(".$searchfield." <> '".$searchstring."')";
			else	
				$where_string = "(".$searchfield." <> '".$searchstring."' or ".$searchfield." is null)";
			if($searchstring == NULL)
			$where_string = "(".$searchfield." not like '' or ".$searchfield." is not NULL)";
			break;
			
		case 'bwt':
			$where_string = $searchfield." like '".$searchstring."%' ";
			break;

		case 'ewt':
			$where_string = $searchfield." like '%".$searchstring."' ";
			break;

		case 'grt':
			$where_string = $searchfield." > '".$searchstring."' ";
			break;

		case 'lst':
			$where_string = $searchfield." < '".$searchstring."' ";
			break;

		case 'grteq':
			$where_string = $searchfield." >= '".$searchstring."' ";
			break;

		case 'lsteq':
			$where_string = $searchfield." <= '".$searchstring."' ";
			break;


	}
	$log->debug("Exiting getSearch_criteria method ...");
	return $where_string;
}

/**This function is returns the where conditions for search
*Param $currentModule - module name
*Returns the where condition to be added in list query in string format
*/

function getWhereCondition($currentModule)
{
	global $log,$default_charset;
	global $column_array,$table_col_array;

        $log->debug("Entering getWhereCondition(".$currentModule.") method ...");
	
	if($_REQUEST['searchtype']=='advance')
	{
		$adv_string='';
		$url_string='';
		if(isset($_REQUEST['search_cnt']))
		$tot_no_criteria = $_REQUEST['search_cnt'];
		if($_REQUEST['matchtype'] == 'all')
			$matchtype = "and";
		else
			$matchtype = "or";
		for($i=0; $i<$tot_no_criteria; $i++)
		{
			if($i == $tot_no_criteria-1)
			$matchtype= "";
			
			$table_colname = 'Fields'.$i;
			$search_condition = 'Condition'.$i;
			$search_value = 'Srch_value'.$i;

			list($tab_col_val,$typeofdata) = split("::::",$_REQUEST[$table_colname]);
			$tab_col = str_replace('\'','',stripslashes($tab_col_val));
			$srch_cond = str_replace('\'','',stripslashes($_REQUEST[$search_condition]));
			$srch_val = addslashes($_REQUEST[$search_value]);
			$srch_val = function_exists(iconv) ? @iconv("UTF-8",$default_charset,$srch_val) : $srch_val;
			list($tab_name,$column_name) = split("[.]",$tab_col);
			$url_string .="&Fields".$i."=".$tab_col."&Condition".$i."=".$srch_cond."&Srch_value".$i."=".$srch_val;
			$uitype=getUItype($currentModule,$column_name);
			//added to allow  search in check box type fields(ex: product active. it will contain 0 or 1) using yes or no instead of 0 or 1
			if ($uitype == 56)
			{
				if(stristr($srch_val,'yes'))
                                        $adv_string .= " ".getSearch_criteria($srch_cond,"1",$tab_name.'.'.$column_name)." ".$matchtype;
                                elseif(stristr($srch_val,'no'))
                                        $adv_string .= " ".getSearch_criteria($srch_cond,"0",$tab_name.'.'.$column_name)." ".$matchtype;
				else
                                        $adv_string .= " ".getSearch_criteria($srch_cond,$srch_val,$tab_name.'.'.$column_name)." ".$matchtype;
			}
			elseif($tab_col == "vtiger_crmentity.smownerid")
			{
				$adv_string .= " (".getSearch_criteria($srch_cond,$srch_val,'vtiger_users.user_name')." or";	
				$adv_string .= " ".getSearch_criteria($srch_cond,$srch_val,'vtiger_groups.groupname')." )".$matchtype;	
			}
			elseif($tab_col == "vtiger_activity.status")
			{
				if($srch_cond == 'dcts' || $srch_cond == 'isn' || $srch_cond == 'is')
					$re_cond = "and";
				else
					$re_cond = "or";
				if($srch_cond == 'is' && $srch_val !='')
					$re_cond = "or";

				$adv_string .= " (".getSearch_criteria($srch_cond,$srch_val,'vtiger_activity.status')." ".$re_cond;
				$adv_string .= " ".getSearch_criteria($srch_cond,$srch_val,'vtiger_activity.eventstatus')." )".$matchtype;	
			}
			elseif($tab_col == "vtiger_cntactivityrel.contactid")
			{
				$adv_string .= " (".getSearch_criteria($srch_cond,$srch_val,'vtiger_contactdetails.firstname')." or";	
				$adv_string .= " ".getSearch_criteria($srch_cond,$srch_val,'vtiger_contactdetails.lastname')." )".$matchtype;	
			}
			elseif(in_array($column_name,$column_array))
                        {
                                $adv_string .= getValuesforColumns($column_name,$srch_val,$srch_cond)." ".$matchtype;
                        }
			else
			{
				$adv_string .= " ".getSearch_criteria($srch_cond,$srch_val,$tab_col)." ".$matchtype;	
			}
		}
		$where="(".$adv_string.")#@@#".$url_string."&searchtype=advance&search_cnt=".$tot_no_criteria."&matchtype=".$_REQUEST['matchtype'];
	}
	elseif($_REQUEST['type']=='dbrd')
	{
		$where = getdashboardcondition();
	}
	else
	{
 		$where=Search($currentModule);
	}
	$log->debug("Exiting getWhereCondition method ...");
	return $where;

}

/**This function is returns the where conditions for dashboard and shows the records when clicked on dashboard graph
*Takes no parameter, process the values got from the html request object
*Returns the search criteria option (where condition) to be added in list query
*/

function getdashboardcondition()
{
	global $adb;
	$where_clauses = Array();
	$url_string = "";

	if (isset($_REQUEST['leadsource'])) $lead_source = $_REQUEST['leadsource'];
	if (isset($_REQUEST['date_closed'])) $date_closed = $_REQUEST['date_closed'];
	if (isset($_REQUEST['sales_stage'])) $sales_stage = $_REQUEST['sales_stage'];
	if (isset($_REQUEST['closingdate_start'])) $date_closed_start = $_REQUEST['closingdate_start'];
	if (isset($_REQUEST['closingdate_end'])) $date_closed_end = $_REQUEST['closingdate_end'];
	if(isset($_REQUEST['owner'])) $owner = $_REQUEST['owner'];
	if(isset($_REQUEST['campaignid'])) $campaign = $_REQUEST['campaignid'];
	if(isset($_REQUEST['quoteid'])) $quote = $_REQUEST['quoteid'];
	if(isset($_REQUEST['invoiceid'])) $invoice = $_REQUEST['invoiceid'];
	if(isset($_REQUEST['purchaseorderid'])) $po = $_REQUEST['purchaseorderid'];

	if(isset($date_closed_start) && $date_closed_start != "" && isset($date_closed_end) && $date_closed_end != "")
	{
		array_push($where_clauses, "vtiger_potential.closingdate >= ".$adb->quote($date_closed_start)." and vtiger_potential.closingdate <= ".$adb->quote($date_closed_end));
		$url_string .= "&closingdate_start=".$date_closed_start."&closingdate_end=".$date_closed_end;
	}
	
	if(isset($sales_stage) && $sales_stage!=''){
		if($sales_stage=='Other')
		array_push($where_clauses, "(vtiger_potential.sales_stage <> 'Closed Won' and vtiger_potential.sales_stage <> 'Closed Lost')");
		else
		array_push($where_clauses, "vtiger_potential.sales_stage = ".$adb->quote($sales_stage));
		$url_string .= "&sales_stage=".$sales_stage;
	}
	if(isset($lead_source) && $lead_source != "") {
		array_push($where_clauses, "vtiger_potential.leadsource = ".$adb->quote($lead_source));
		$url_string .= "&leadsource=".$lead_source;
	}
	if(isset($date_closed) && $date_closed != "") {
		array_push($where_clauses, $adb->getDBDateString("vtiger_potential.closingdate")." like ".$adb->quote($date_closed.'%')."");
		$url_string .= "&date_closed=".$date_closed;
	}
	if(isset($owner) && $owner != ""){
		$user_qry="select vtiger_users.id from vtiger_users where vtiger_users.user_name = '".$owner."' ";
		$res = $adb->query($user_qry);
		$uid = $adb->query_result($res,0,'id');
		array_push($where_clauses, "vtiger_crmentity.smownerid = ".$uid);
		$url_string .= "&assigned_user_id=".$uid;
	}
	if(isset($campaign) && $campaign != "")
	{
		array_push($where_clauses, "vtiger_campaigncontrel.campaignid = ".$campaign);
                $url_string .= "&campaignid=".$campaign;
	}
	if(isset($quote) && $quote != "")
	{
		array_push($where_clauses, "vtiger_inventoryproductrel.id = ".$quote);
		$url_string .= "&quoteid=".$quote;
	}
	if(isset($invoice) && $invoice != "")
	{
		array_push($where_clauses, "vtiger_inventoryproductrel.id = ".$invoice);
		$url_string .= "&invoiceid=".$invoice;
	}
	if(isset($po) && $po != "")
	{
		array_push($where_clauses, "vtiger_inventoryproductrel.id = ".$po);
		$url_string .= "&purchaseorderid=".$po;
	}

	$where = "";
	foreach($where_clauses as $clause)
	{
		if($where != "")
		$where .= " and ";
		$where .= $clause;
	}
	return $where."#@@#".$url_string;
}

/**This function is used to replace only the first occurence of a given string
Param $needle - string to be replaced
Param $replace - string to be replaced with
Param $replace - given string
Return type is string
*/
function str_replace_once($needle, $replace, $haystack)
{
	// Looks for the first occurence of $needle in $haystack
	// and replaces it with $replace.
	$pos = strpos($haystack, $needle);
	if ($pos === false) {
		// Nothing found
		return $haystack;
	}
	return substr_replace($haystack, $replace, $pos, strlen($needle));
}
?>
