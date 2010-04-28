<?php
// $Header: /cvsroot/bitweaver/_bit_libertyform/LibertyForm.php,v 1.32 2010/04/28 14:25:03 dansut Exp $
/**
 * LibertyForm is an intermediary object designed to hold the code for dealing with generic
 * GUI forms based on Liberty Mime objects, and their processing.  It probably shouldn't ever
 * be instantiated
 *
 * date created 2009-Jul-22
 * @author Daniel Sutcliffe
 * @version $Revision: 1.32 $
 * @package LibertyForm
 */

require_once(LIBERTY_PKG_PATH.'LibertyMime.php');

class LibertyForm extends LibertyMime {
	protected $mId;
	protected $mFields;
	private $mFormTbl;
	private $mFormSeq;
	private $mChildPkgName;
	protected $mChildIdName;
	protected $mBaseURL;

// {{{ ---- public functions ----
	// {{{ __construct()
	/**
	 * @param int $pId database Id of exiting object of this type
	 * @param int $pContentId database Id of existing LibertyContent object
	 */
	function __construct($pId=NULL, $pContentId=NULL, $pPkgName, $pFormTbl, $pFormSeq) {
		parent::__construct();
		$this->mId = $pId;
		$this->mContentId = $pContentId;
		$this->mFormTbl = BIT_DB_PREFIX.$pFormTbl;
		$this->mFormSeq = $pFormSeq;
		$this->mChildPkgName = $pPkgName;
		$this->mChildIdName = $pPkgName.'_id';
		$this->mBaseURL = constant(strtoupper($pPkgName).'_PKG_URL');

		// Save typing and errors, give each an element equal to its key name.
		foreach($this->mFields as $name => &$val) $val['fieldname'] = $name;
	} // }}}

	// {{{ load() get data from the database either by object or libertyContent's id
	/**
	 * If this object constructed with a valid Id then load from the DB
	 * @return boolean TRUE on success, FALSE on failure - mErrors will contain reason for failure
	 */
	public function load() {
		if($this->verifyId($this->mId) || $this->verifyId($this->mContentId)) {
			$lookupColumn = ($this->verifyId($this->mId) ?  $this->mChildIdName : 'content_id');
			$bindVars = array();
			$selectSql = $joinSql = $whereSql = '';
			array_push($bindVars, $lookupId = (@BitBase::verifyId($this->mId) ? $this->mId : $this->mContentId));
			$this->getServicesSql('content_load_sql_function', $selectSql, $joinSql, $whereSql, $bindVars);

			$query = "
				SELECT data.*, lc.*,
					uue.`login` AS modifier_user, uue.`real_name` AS modifier_real_name,
					uuc.`login` AS creator_user, uuc.`real_name` AS creator_real_name
					$selectSql
				FROM `".$this->mFormTbl."` data
					INNER JOIN `".BIT_DB_PREFIX."liberty_content` lc ON (lc.`content_id` = data.`content_id`)
					LEFT JOIN `".BIT_DB_PREFIX."users_users` uue ON (uue.`user_id` = lc.`modifier_user_id`)
					LEFT JOIN `".BIT_DB_PREFIX."users_users` uuc ON (uuc.`user_id` = lc.`user_id`)
					$joinSql
				WHERE (data.`$lookupColumn` = ?)
					$whereSql";
			$result = $this->mDb->query($query, $bindVars);

			if($result && $result->numRows()) {
				$this->mInfo = $result->fields;
				$this->mContentId = $result->fields['content_id'];
				$this->mId = $result->fields[$this->mChildIdName];

				$this->mInfo['creator'] = (!empty($result->fields['creator_real_name']) ? $result->fields['creator_real_name'] : $result->fields['creator_user']);
				$this->mInfo['editor'] = (!empty($result->fields['modifier_real_name']) ? $result->fields['modifier_real_name'] : $result->fields['modifier_user']);
				$this->mInfo['display_name'] = BitUser::getTitle($this->mInfo);
				$this->mInfo['display_url'] = $this->getDisplayUrl();
				$this->mInfo['edit_url'] = $this->getEditUrl();
				$this->mInfo['remove_url'] = $this->getRemoveUrl();
				$this->mInfo['parsed_data'] = $this->parseData();

				parent::load();
			}
		}
		return(count($this->mInfo));
	} // }}} load()

	// {{{ store() update, or create, this objects data in the database
	/**
	 * @param array $pParamHash hash of values that will be used to store the page
	 * @return boolean TRUE on success, FALSE on failure - mErrors will contain reason for failure
	 */
	public function store(&$pParamHash=NULL) {
		if(empty($this->mChildPkgName)) {
			$this->mErrors['store'] = "Configuration error, don't know package name";
			return(FALSE);
		}
		// If no updated data hash passed in fake pParamHash from objects existing data
		if($pParamHash == NULL) $pParamHash = $this->fakeStoreHash();
		$childStore = $this->mChildPkgName.'_store';
		if($this->verifyData($pParamHash, $childStore) && parent::store($pParamHash)) {
			$this->mDb->StartTrans();
			if($this->mId) {
				if(isset($pParamHash[$this->mChildIdName]) && ($pParamHash[$this->mChildIdName] != $this->mId)) {
					$this->mErrors['store'] = "Attempting to change id of existing data is not supported.";
					return FALSE;
				}
				$locId = array($this->mChildIdName => $this->mId);
				$result = $this->mDb->associateUpdate($this->mFormTbl, $pParamHash[$childStore], $locId);
			} else {
				$pParamHash[$childStore][$this->mChildIdName] = $this->mDb->GenID($this->mFormSeq);
				$pParamHash[$childStore]['content_id'] = $pParamHash['content_id'];
				$result = $this->mDb->associateInsert($this->mFormTbl, $pParamHash[$childStore]);
				// result checking ... nah, why bother !?
				$this->mId = $pParamHash[$childStore][$this->mChildIdName];
			}
			if(!empty($pParamHash[$childStore.'_multiple'])) {
				foreach($pParamHash[$childStore.'_multiple'] as $multiTbl => $bindvarray) {
					// first remove any existing entries
					$query = "DELETE FROM `".BIT_DB_PREFIX.$multiTbl."` WHERE (`".$this->mChildIdName."` = ?) ";
					$result = $this->mDb->query($query, array($this->mId));
					// then insert any new or modified values
					foreach($bindvarray as $bindvar) {
						$bindvar[$this->mChildIdName] = $this->mId; // has to happen here for fresh creates
						$result = $this->mDb->associateInsert(BIT_DB_PREFIX.$multiTbl, $bindvar);
					}
				}
			}
			$this->mDb->CompleteTrans();
			$this->load();
		} else {
			$this->mErrors['store'] = "Failed to save the {$this->mChildPkgName} data.";
		}

		return(count($this->mErrors) == 0);
	} // }}} store()

	// {{{ expunge() remove the object from the database
	/**
	 * @return boolean TRUE on success, FALSE on failure
	 */
	public function expunge() {
		global $gBitSystem;
		$ret = FALSE;
		if($this->isValid()) {
			$this->mDb->StartTrans();
			$query = "DELETE FROM `".$this->mFormTbl."` WHERE (`content_id` = ?) ";
			$result = $this->mDb->query($query, array($this->mContentId));
			if(parent::expunge()) {
				$ret = TRUE;
			}
			$this->mDb->CompleteTrans();
		}
		return $ret;
	} // }}} expunge()

	// {{{ isValid() Make sure the package is loaded and valid
	/**
	 * @return boolean TRUE on success, FALSE on failure
	 */
	public function isValid() {
		return(@BitBase::verifyId($this->mId) && @BitBase::verifyId($this->mContentId));
	} /// }}}

	// {{{ setField() set a value in the mInfo hash
	/**
	 * @param string $pKey the key into the mInfo hash
	 * @param value $pValue the value to set the hash element to
	 * @return boolean whether the field was sucessfully set
	 */
	public function setField($pKey, $pValue) {
		// TODO sanity checking should be here otherwise may as well access mInfo directly
		$this->mInfo[$pKey] = $pValue;
		return TRUE;
	} // }}} setField()

	// {{{ getFields() get the gui form elements to edit and display the data
	/**
	 * @param array hash of fields(keys) the caller wants to see in returned array, unlimited if not specified
	 * @return array List of this objects GUI fields
	 */
	public function getFields($pWantedFields=NULL) {
		$fields = (($pWantedFields == NULL) ? $this->mFields : array_intersect_key($this->mFields, $pWantedFields));
		// call a seperate private function to do the work as it might need to call itself to process sub forms
		self::privateGetFields($fields, $pWantedFields);

		return $fields;
	} // }}} getFields()

	// {{{ getDisplayUrl() generates the URL to view this object
	/**
	 * @return string URL of this objects view page
	 */
	public function getDisplayUrl() {
		return (@$this->isValid() ? $this->getUrl($this->mBaseURL, $this->mChildIdName, $this->mId) : NULL);
	} // }}} getDisplayUrl()

	// {{{ getEditUrl() generates the URL to edit this object
	/**
	 * @return string URL of this objects edit page
	 */
	public function getEditUrl() {
		return (@$this->isValid() ? $this->getUrl($this->mBaseURL, $this->mChildIdName, $this->mId, "edit") : NULL);
	} // }}} getEditUrl()

	// {{{ getRemoveUrl() generates the URL to remove this object
	/**
	 * @return string URL of this objects edit page
	 */
	public function getRemoveUrl() {
		return (@$this->isValid() ? $this->getUrl($this->mBaseURL, $this->mChildIdName, $this->mId, "remove") : NULL);
	} // }}} getEditUrl()
// }}} ---- end public functions

// {{{ ---- protected functions ----
	// {{{ fakeStoreHash() produce a param hash for self::store() using existing data/defaults
	/**
	 * @return array param hash that can be passed to self::store() to cause DB to update
	 */
	protected function fakeStoreHash() {
		$paramHash = array();
		// LibertyContent items
		if(array_key_exists('title', $this->mInfo)) $paramHash['title'] = $this->mInfo['title'];

		// Real work done by a seperate function as may need calling recursively
		$this->buildFakeHash($this->getFields(), $paramHash);

		return $paramHash;
	} // }}} fakeStoreHash()

	// {{{ buildFakeHash() process gui form elements
	/**
	 * Function is called from fakeStoreHash, is seperate func so it can be recursive for sub forms
	 */
	protected function buildFakeHash($pFields, &$pParamHash) {
		// The derived field elements
		foreach($pFields as $fieldname => $field) {
			if(!isset($field['type'])) $field['type'] = 'default'; // This shouldn't really happen
			// If object has a value set for the field use this
			if(array_key_exists($fieldname, $this->mInfo)) {
				// Certain field types are weird and need special processing
				if($field['type'] == 'multiple') {
					$idx = 1;
					foreach($this->mInfo[$fieldname] as $mfid => $mfval) {
						foreach($field['fields'] as $mfname => $mfattrs) {
							switch($mfattrs['type']) {
								case 'remove':
									// ignore
									break;
								case 'checkbox':
									if($mfval[$mfname] == 'y') $pParamHash[$fieldname][$mfname][$idx] = $idx;
									break;
								case 'boolack':
									$pParamHash[$fieldname][$mfname][$idx] = self::boolackHash($mfval[$mfname]);
									break;
								case 'radio':
									if(!empty($this->mInfo[$mfname]) && ($this->mInfo[$mfname] == $mfid)) {
										$pParamHash[$fieldname][$mfname] = $idx;
									}
									break;
								default:
									$pParamHash[$fieldname][$mfname][$idx] = $mfval[$mfname];
									break;
							}
						}
						$idx++;
					}
				} elseif($field['type'] == 'boolack') {
					$pParamHash[$fieldname] = self::boolackHash($this->mInfo[$fieldname]);
				} elseif($field['type'] == 'currency') {
					$pParamHash[$fieldname]['unit'] = intval($this->mInfo[$fieldname] / 100);
					$pParamHash[$fieldname]['frac'] = abs($this->mInfo[$fieldname] % 100);
				} else {
					$pParamHash[$fieldname] = $this->mInfo[$fieldname];
				}
			// Else if there exists a default value then use this
			} elseif(array_key_exists('defval', $field)) {
				$pParamHash[$fieldname] = $field['defval'];
			}
			if($field['type'] == 'boolfields') $this->buildFakeHash($field['fields'], $pParamHash);
		}
		if(!isset($pParamHash[$this->mChildIdName]) && $this->mId) $pParamHash[$this->mChildIdName] = $this->mId;
	} // }}} buildFakeHash()

	// {{{ boolackHash() helper function for boolack fields and buildFakeHash
	protected static function boolackHash($pFieldVal) {
		$ret = NULL;
		switch($pFieldVal) {
			case 'a':
				$ret[] = 'a';
				// No break - fallthru to 'y' is intentional
			case 'y':
				$ret[] = 'y';
				break;
			case 'n':
			default:
				// No entry in param hash, just like a real form would do
				break;
		}
		return $ret;
	} // }}} boolackHash()

	// {{{ verifyData() make sure the data is safe to store
	/** This function is responsible for data integrity and validation before any operations are performed with the $pParamHash
	 *
	 * @param array $pParamHash reference to hash of values to be used to store object, they will be modified where necessary
	 * @param string $pChildStore name of child object derived from this that wants to do the store
	 * @return boolean TRUE on success - $this->mErrors will contain reason(s) for failure
	 */
	protected function verifyData(&$pParamHash, $pChildStore) {
		$pParamHash[$pChildStore] = array();
		$pParamHash[$pChildStore.'_multiple'] = array();

		// make sure we're all loaded up if we have a mId
		if($this->verifyId($this->mId) && empty($this->mInfo)) {
			$this->load();
		}

		if(@$this->verifyId($this->mInfo['content_id'])) {
			$pParamHash['content_id'] = $this->mInfo['content_id'];
		}

		if(@$this->verifyId($pParamHash['content_id'])) {
			$pParamHash[$pChildStore]['content_id'] = $pParamHash['content_id'];
		}

		// It is possible a derived class set this to something different
		if(@$this->verifyId($pParamHash['content_type_guid'])) {
			$pParamHash['content_type_guid'] = $this->mContentTypeGuid;
		}

		if(!empty($pParamHash['data'])) {
			$pParamHash['edit'] = $pParamHash['data'];
		} elseif(!empty($this->mContentId) && !isset($pParamHash['edit'])) {
			// If we don't do this then the data portion is deleted on a partial store
			$pParamHash['edit'] = $this->mInfo['data'];
		}

		if(!empty($pParamHash['title'])) {
			$pParamHash['content_store']['title'] = substr($pParamHash['title'], 0, 160);
			$this->mInfo['title'] = $pParamHash['content_store']['title'];
		}

		// Most of the fields for this object (its children) are defined in a big array, we call a
		// private function to deal with this as it might want to call itself to deal with sub forms
		$this->verifyFields($this->getFields(), $pParamHash, $pChildStore);

		return(count($this->mErrors) == 0);
	} // }}} verifyData()
	// {{{ verify() old version of above function - has to be public as parent object uses old PHP OOP
	public final function verify(&$paramHash) {
		$this->mErrors['verify'] = "::verify() should not be called directly";
		return FALSE; // Always Fail!
	} // }}} verify()
// }}} ---- end protected functions ----

// {{{ ---- static functions ----
	// {{{ storeConfigs()
	static function storeConfigs($pRequest, $pChildPkgName) {
		global $gBitSystem;
		foreach($pRequest['_fields'] as $fieldname => $type) {
			switch($type) {
			  case 'checkbox':
				$gBitSystem->storeConfig($pChildPkgName.'_'.$fieldname,
					(isset($pRequest[$fieldname]) ? $pRequest[$fieldname] : 'n'),
					$pChildPkgName);
				break;
			  default;
				$gBitSystem->storeConfig($pChildPkgName.'_'.$fieldname,
					(isset($pRequest[$fieldname]) ? $pRequest[$fieldname] : ''),
					$pChildPkgName);
				break;
			}
		}
	} // }}} storeConfigs()

	// {{{ getUrl() generates a URL to gain access to a specific type of function on this object
	/**
	 * @return string URL of this objects edit page
	 */
	static function getUrl($pBaseURL, $pChildIdName, $pId, $pFunction="") {
		global $gBitSystem;
		if(empty($pFunction)) {
			$pretty_func = "";
			$normal_func = "index";
		} else {
			$pretty_func = $pFunction."/";
			$normal_func = $pFunction;
		}
		$ret = $pBaseURL;
		if($gBitSystem->isFeatureActive('pretty_urls') || $gBitSystem->isFeatureActive('pretty_urls_extended')) {
			$ret .= $pretty_func.$pId;
		} else {
			$ret .= $normal_func.".php?".$pChildIdName."=".$pId;
		}
		return $ret;
	} // }}} getUrl()
// }}} ---- end static functions ----

// {{{ ---- private functions ----
	// {{{ verifyFields() process gui form elements
	/**
	 * Function is called from verifyData, is seperate func so it can be recursively called for sub forms
	 */
	private function verifyFields($fields, &$pParamHash, $pChildStore) {
		// This conditional has to be done due to a bug inherited from Sample pkg that stopped Liberty Services storing.
		// $this->store() was passed $_REQUEST[$this->mChildPkgName] which effectively stopped store() seeing any LS data.
		// $this->store() now should always be passed $_REQUEST,
		// so we SHOULD have pkg data in the pkgname hash key, or it MAY be still at top level of $pParamHash until all fixed.
		if(!empty($pParamHash[$this->mChildPkgName]) && is_array($pParamHash[$this->mChildPkgName])) {
			// So this is the good/normal case heading forward
			$pkgData =& $pParamHash[$this->mChildPkgName];
		} else {
			// If this is the case then ::store($_REQUEST['<pkgname>']) was called and any LibertyServices data will not be saved
			error_log('LibertyContent::store() should be called using $_REQUEST, not $_REQUEST[\''.$this->mChildPkgName.'\']');
			$pkgData =& $pParamHash;
		}
		$pkgStore =& $pParamHash[$pChildStore];
		$pkgStoreMulti = & $pParamHash[$pChildStore.'_multiple'];

		// Set boolean if the paramhash contains a hash specifying a limited set of fields
		$limitedFieldset = (isset($pkgData['_fields']) && is_array($pkgData['_fields']));
		// Loop through the given fields and process each one
		foreach($fields as $fieldname => $field) {
			// Defaults to stop PHP whining
			if(!isset($field['type'])) $field['type'] = 'text';
			if(!isset($field['typopt'])) $field['typopt'] = '';

			// Ignore any fields that are not set in the special '_fields' part of the param Hash.
			if($limitedFieldset && !isset($pkgData['_fields'][$fieldname])) continue;

			// If field is in the hash then it is being changed
			if(array_key_exists($fieldname, $pkgData)) {
				if(!empty($field['chkenables']) && empty($pkgData[$fieldname.'_chk'])) { // Unchecked so value is to be NULL
					$pkgStore[$fieldname] = NULL; // Specifically clear contents
				} elseif(empty($pkgData[$fieldname]) && isset($field['required'])) {
					$this->mErrors[$fieldname] = $field['description']." is a required field.";
				} elseif($field['type'] == "date") {
					$pkgStore[$fieldname] =
						$pkgData[$fieldname]['Year']."-".$pkgData[$fieldname]['Month']."-".$pkgData[$fieldname]['Day'];
				} elseif((($field['type'] == 'options') && ($field['typopt'] == 'multiple')) ||
				         ($field['type'] == 'checkboxes')) { // Deal with fields that contain multiple values
					// This assumes the field is a bitfield - may want to have other choices ...
					if(is_array($pkgData[$fieldname])) {
						$pkgStore[$fieldname] = array_sum($pkgData[$fieldname]);
					} else {
						$this->mErrors[$fieldname] = "Expecting bitfield array and got '".$pkgData[$fieldname]."'.";
					}
				} elseif(($field['type'] == 'text') && isset($field['maxlen']) && !empty($field['maxlen'])) { 
					$pkgStore[$fieldname] = substr($pkgData[$fieldname], 0, $field['maxlen']);
				} elseif($field['type'] == "package_id") {
					// Experimental stuff
					if(empty($field['content_type_guid'])) {
						$this->mErrors[$fieldname] = "Badly configured ".$field['description'].".";
					} else {
						global $gLibertySystem;
						$content = $gLibertySystem->getLibertyClass($field['content_type_guid']);
						if(method_exists($content, 'getData') &&
						   ($content->getData($pkgData[$fieldname]) != NULL)) {
							$pkgStore[$fieldname] = $pkgData[$fieldname];
						} else {
							$this->mErrors[$fieldname] = "Given value does not match any existing ".$field['description'].".";
						}
					}
				} elseif($field['type'] == "boolack") {
					if(!is_array($pkgData[$fieldname])) { // Something is broken
						$pkgStore[$fieldname] = NULL;
					} elseif(in_array('a', $pkgData[$fieldname]) && in_array('y', $pkgData[$fieldname])) {
						$pkgStore[$fieldname] = 'a';
					} elseif(in_array('y', $pkgData[$fieldname])) {
						$pkgStore[$fieldname] = 'y';
					} else { // Something weird is going on
						$pkgStore[$fieldname] = 'n';
					}
				} elseif($field['type'] == "currency") {
					if(!(empty($pkgData[$fieldname]['frac']) || is_numeric($pkgData[$fieldname]['frac'])) ||
					   !(empty($pkgData[$fieldname]['unit']) || is_numeric($pkgData[$fieldname]['unit']))) {
						$this->mErrors[$fieldname] = $field['description']." must be numeric.";
					} else {
						$tmpval = ($pkgData[$fieldname]['unit'] * 100);
						$tmpval += ((($tmpval<0)?-1:1) * abs($pkgData[$fieldname]['frac']));
						$pkgStore[$fieldname] = $tmpval;
						if(isset($field['required']) && empty($pkgStore[$fieldname])) {
							$this->mErrors[$fieldname] = $field['description']." must be non zero.";
						}
					}
				} elseif($field['type'] == "boolfields") {
					// Actual field is very simple to deal with and could be dealt with by default else, but ...
					$pkgStore[$fieldname] = $pkgData[$fieldname];
					// If 'yes' then also need to deal with the subform it contains by calling ourself recursvely
					if($pkgData[$fieldname] == 'y') $this->verifyFields($field['fields'], $pParamHash, $pChildStore);
				} elseif($field['type'] == "multiple") {
					$bindvarray = array(); // This is the array that may eventually get passed back to store()
					$checkboxcols = array(); // Checkboxes don't give feedback on 'no check' so dealt with differently
					$reqcols = array(); // Array of column names that reuire values to be filled in
					$removeidxs = array(); // Array of $bindvarray indexes that need removing by user request
					$radiocols = array(); // Array of columns that contain groups of radio buttons, index column name
					foreach($field['fields'] as $colname => $colattrs) {
						$colvals = (isset($pkgData[$fieldname][$colname]) ? $pkgData[$fieldname][$colname] : array());
						if(($colattrs['type'] != 'radio') && // Radio columns aren't really multifields
						   isset($colattrs['required']) && $colattrs['required']) {
							$reqcols[$colname] = $colname; // Keep track of required colums for multifields
						}
						switch($colattrs['type']) {
							case 'checkbox':
								$checkboxcols[$colname] = $colvals;
								break;
							case 'radio':
								$radiocols[$colname] = (empty($colvals) ? 0 : $colvals);
								break;
							case 'remove':
								$removeidxs = $colvals; // Should only be one 'remove' column
								break;
							default:
								foreach($colvals as $idx => $colval) {
									if($colattrs['type'] == 'boolack') {
										if(!is_array($colval)) { // Something is broken
											$bindvarray[$idx][$colname] = NULL;
										} elseif(in_array('a', $colval) && in_array('y', $colval)) {
											$bindvarray[$idx][$colname] = 'a';
										} elseif(in_array('y', $colval)) {
											$bindvarray[$idx][$colname] = 'y';
										} else { // Something weird is going on
											$bindvarray[$idx][$colname] = 'n';
										}
									} else {
										$bindvarray[$idx][$colname] = $colval;
									}
								}
								break;
						}
					}
					// Remove any rows that have a check in the remove checkbox
					foreach($removeidxs as $idx) unset($bindvarray[$idx]);
					// Loop through the bindvariables of our multiple elements doing some field fixup
					foreach($bindvarray as $idx => &$bindvar) {
						// Remove any rows with required columns that aren't filled in
						foreach($reqcols as $reqcolname) {
							if(!isset($bindvar[$reqcolname]) || empty($bindvar[$reqcolname])) {
								unset($bindvarray[$idx]);
								continue 2; // next $bindvar in the bindvarray
							}
						}
						// Add the values from the checkbox columns
						foreach($checkboxcols as $colname => $colvals) {
							$bindvar[$colname] = ((in_array($idx, $colvals)) ? 'y' : 'n');
						}
						if(empty($bindvar[$field['idfield']]) && // If empty Id field it means this is a brand new element
						   isset($field['sequence'])) { // So if there is a sequence table
							$bindvar[$field['idfield']] = $this->mDb->GenID($field['sequence']); // Use this to generate new Id
						}
					}
					// The $radiocols array from multiple fields is used to set regular (not multiple) fields
					foreach($radiocols as $rfname => $idx) {
						$pkgStore[$rfname] = (isset($bindvarray[$idx]) ? $bindvarray[$idx][$field['idfield']] : 0);
						if(($pkgStore[$rfname] > 0) || empty($bindvarray)) {
							$this->mInfo[$rfname] = $pkgStore[$rfname];
						} elseif(isset($field['fields'][$rfname]['required']) && ($field['fields'][$rfname]['required'] == TRUE)) {
							$this->mErrors[$fieldname] = $field['fields'][$rfname]['description']." needs a selection.";
						}
					}
					if(isset($field['required']) && empty($bindvarray)) {
						$this->mErrors[$fieldname] = $field['description']." needs at least one entry.";
					} else {
						$pkgStoreMulti[$fieldname] = $bindvarray;
					}
				} else { // Any other fields just need to be saved in the DB under their fieldname
					$pkgStore[$fieldname] = $pkgData[$fieldname];
				}
			// If field is not in hash but it is a required one we need to do more checking
			} elseif(isset($field['required']) && $field['required']) {
				if((($field['type'] == 'options') && ($field['typopt'] == 'multiple')) ||
				   ($field['type'] == 'checkboxes')) {
					$this->mErrors[$fieldname] = $field['description']." needs at least one option selected.";
				} elseif($field['type'] == 'multiple') {
					$this->mErrors[$fieldname] = $field['description']." needs at least one value.";
				} elseif(!isset($this->mInfo[$fieldname])) {
					$this->mErrors[$fieldname] = $field['description']." is a required field.";
				}
			} elseif($field['type'] == "multiple") { // non required multiple and form not providing any vals
				// Need these keys (represents table name) to exist to make sure any existing entries get erased
				$pkgStoreMulti[$pChildStore.'_multiple'][$fieldname] = array();
			} elseif(($field['type'] == "checkboxes") || ($field['type'] == "checkbox") ||
			         ($field['type'] == "boolack") || ($field['type'] == "boolfields")) {
			// Empty checkbox based fields don't report their existance when empty ...
				$pkgStore[$fieldname] = ''; // but still need to be zeroed
			}
			// This updates the mInfo, with validated fields even if this function will fail
			// The reason to do this is so that the good fields stay populate when form redisplayed
			if(array_key_exists($fieldname, $pkgStore)) {
				$this->mInfo[$fieldname] = $pkgStore[$fieldname];
			}
		} // end Loop through fields
	} // }}} verifyFields()

	// {{{ privateGetFields() process gui form elements
	/**
	 * Function is called from getFields, is seperate func so it can be recursive for sub forms
	 * @param array hash of fields(keys) the caller wants to see in returned array, unlimited if not specified
	 */
	private function privateGetFields(&$pFields, $pWantedFields=NULL) {
		// Populate any current values that are already set
		foreach($pFields as $name => &$val) {
			$val['value'] = NULL;
			if(!empty($this->mInfo[$name])) {
				$val['value'] = $this->mInfo[$name];
			} elseif(array_key_exists('defval', $val)) {
				$val['value'] = $val['defval'];
			}
			// Some of the fields with specific types need 'special' processing to set their values up
			if(isset($val['type'])) {
				// Special processing required to get live values into multiple-radio fields
				if($val['type'] == 'multiple') {
					foreach($val['fields'] as $mfname => &$mfval) {
						$mfval['value'] = ((($mfval['type'] == 'radio') && isset($this->mInfo[$mfname])) ? $this->mInfo[$mfname] : NULL);
					}
				} elseif($val['type'] == 'radios') {
					if(empty($val['value'])) unset($val['value']);
				} elseif($val['type'] == 'boolfields') {
					$subwanted = NULL;
					if(isset($pWantedFields) && is_array($pWantedFields[$name])) {
						$subwanted = $pWantedFields[$name];
						foreach($val['fields'] as $name => $field) {
							if(!array_key_exists($name, $subwanted)) unset($val['fields'][$name]);
						}
					}
					$this->privateGetFields($val['fields'], $subwanted);
				} elseif(($val['type'] == 'checkboxes') && (array_key_exists(0, $val['options']))) {
					// A checkboxes options array with a key of zero is assumed to have bad bitmap keys
					$fixed_options = array();
					$bit = 1;
					foreach($val['options'] as $option) {
						$fixed_options[$bit] = $option;
						$bit <<= 1; // shift to the next bit
					}
					$val['options'] = $fixed_options;
				}
			}
		}
	} // }}} privateGetFields()
// }}} ---- end private functions ----

} // LibertyForm class
/* vim: :set fdm=marker : */
?>
