<?php

/**
 * PHP library for Myrrix REST API calls
 *
 * does not need any special installation, nor any external framework like Guzzle or Composer
 * @require lib::curl
 * @version alfa 0.0
 * @todo	implemented only few REST calls, others tbd
 *
 * http://myrrix.com/rest-api
 * @author ulkas
 */
class myrrixRESTlibrary {
	protected $serverUrl	=	"10.10.0.119";
	protected $serverPort	=	6685;
	protected $dataFile		=	"";
	protected $methodType	=	"POST";
	protected $userAgent	=	"myrrix REST library for PHP";
	protected $connectionTimeout		=	2;
	protected $executionTimeout			=	10;
	protected $ok			=	false;
	public	$debug		= false;

	/**
	 * @param string $serverUrl
	 * @param int $serverPort
	 * @param string $dataFile
	 * @param string $methodType
	 */
	public function __construct($serverUrl="",$serverPort="",$dataFile="",$methodType=""){
		if(filter_var($serverUrl, FILTER_VALIDATE_URL))$this->serverUrl=$serverUrl;
		if($dataFile)$this->dataFile=$dataFile;
		if($methodType)$this->methodType=$methodType;
		if(intval($serverPort))$this->serverPort=intval($serverPort);
		//test connection
		$this->ready();
	}
	/**
	 * wheter the server is ready to query
	 *
	 * 302 Temporary Redirect if, in a distributed environment, another partition should handle the request
	 400 Bad Request if the arguments are invalid, like a non-numeric ID
	 401 Unauthorized if a username/password is required, but not supplied correctly in the request via HTTP DIGEST
	 405 Method Not Allowed if an incorrect HTTP method is used, like GET where POST is required
	 500 Internal Server Error if an unexpected server-side exception occurs
	 503 Service Unavailable if no model is yet available to serve requests
	 *
	 * @return boolean
	 */
	protected function ok(){
		switch ($this->ok){
			case 200: return true;break;
			case 302: return true;break;
			case 400: return true;break;
			case 401: return true;break;
			case 405: return true;break;
			case true: return true;break;
			case 404: return false; break;
			case false: return false;break;
			default: return false;
		}
	}
	/**
	 * curl query to server
	 * @param string $path
	 * @param string $method
	 * @param array $data
	 */
	protected function curl($path,$method="",$data=array(),$header=array(),$body=""){
		$postparams="";
		if(is_array($data)){
			foreach ($data as $key=>$value) {
				$postparams.=$key.'='.$value.'&';
			}
		}
		$postparams=trim($postparams,'&');
		$target_url= trim($this->serverUrl,'/').':'.$this->serverPort.'/'.trim($path,'/');
		$userAgent = $this->userAgent;
		if(!$method)$method=$this->methodType;
		if($this->debug){
			var_dump($target_url);
			var_dump($method);
			var_dump($postparams);
			var_dump($header);
		}
		$ch = curl_init();
		if($method=='POST'){
			curl_setopt($ch,CURLOPT_POST, count($data));
			curl_setopt($ch,CURLOPT_POSTFIELDS, $postparams);
		}
		if(is_array($header)){
			curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
		}
		if($body){
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST,$method);
			curl_setopt($ch, CURLOPT_POSTFIELDS,$body);
		}
		if($method=='DELETE')curl_setopt($ch, CURLOPT_CUSTOMREQUEST,$method);
		
		curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
		curl_setopt($ch, CURLOPT_URL,$target_url);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->executionTimeout);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectionTimeout);
		$html= curl_exec($ch);
		$code=curl_getinfo($ch,CURLINFO_HTTP_CODE);

		if (strlen(curl_errno($ch)>0)) {
			$html= "error:" .curl_errno($ch);
			$html .= " | " . curl_error($ch);
		}
		curl_close($ch);
		return array($code,$html);
	}
	/**
	 * universal requester, serves for parsing html return codes
	 *
	 * All API methods return the following HTTP statuses in certain situations:

	 302 Temporary Redirect if, in a distributed environment, another partition should handle the request
	 400 Bad Request if the arguments are invalid, like a non-numeric ID
	 401 Unauthorized if a username/password is required, but not supplied correctly in the request via HTTP DIGEST
	 405 Method Not Allowed if an incorrect HTTP method is used, like GET where POST is required
	 500 Internal Server Error if an unexpected server-side exception occurs
	 503 Service Unavailable if no model is yet available to serve requests

		In these cases, the body of the response will instead contain information more appropriate to the response.
		For example a 302 redirect will have an empty body; a 500 error will contain a stack trace.
		Other special statuses are noted below. Status is 200 OK in normal operation.
	 *
	 * @param string $path
	 * @param string $method
	 * @param array $data
	 */
	protected function call($path,$method="",$data=array(),$body=""){
		list($code,$html)=$this->curl($path,$method,$data,"",$body);
		$res=false;
		if($this->debug)var_dump($code);
		switch ($code){
			case 200:	$res=true; break;
			//TODO: add additional codes if neccessary
			default:	$res=false; break;
		}
		return $res;
	}
	/**
	 * universal requester, serves for parsing html body response
	 * @param string $path
	 * @param string $method
	 * @param array $data
	 */
	protected function request($path,$method="",$data=array(),$header=array()){
		list($code,$html)=$this->curl($path,$method,$data,$header);
		if($code==200)return $html;
		else {
			if($this->debug)var_dump($html);
			return false;
		}
	}
	/**
	 * parses csv text data into php array
	 * @param string $data
	 * @return array(id=>pref)
	 */
	public static function CSVtoArray($data){
		//might get false data when wrong request
		if(!$data)return $data;
		//parsing CSV text response into array(item=>preference);
		$data=explode("\n", $data);
		$res=array();
		foreach ($data as $value) {
			$value=trim($value);
			if(!$value)continue;
			$value=explode(",", $value);
			if(count($value)>1)$res[$value[0]]=$value[1];
			else $res[]=$value[0];
		}
		return $res;
	}

	/**#@+
	 * REST query methods
	 */
	/**
	 * http://myrrix.com/rest-api/#ready
	 * @return boolean
	 */
	public function ready(){
		$path="/ready";
		$method="HEAD";
		//set ok status when testing readiness of server
		list($this->ok,$res)=$this->curl($path,$method);
		return $this->ok();
	}
	/**
	 * http://myrrix.com/rest-api/#refresh
	 * @return boolean
	 */
	public function refresh(){
		$path="/refresh";
		$method="POST";
		return $this->call($path,$method);
	}
	/**
	 * http://myrrix.com/rest-api/#recommend
	 * @return array(item=>preference)
	 */
	public function recommend($userid,$howmany=20,$considerKnownItems=false,$rescorerParams=""){
		$path="/recommend/".$userid.'?howMany='.$howmany.'&considerKnownItems='.$considerKnownItems.'&rescorerParams='.$rescorerParams;
		$method="GET";
		$header=array('Accept: text/csv');
		$data=$this->request($path,$method,"",$header);
		return self::CSVtoArray($data);
	}
	/**
	 *http://myrrix.com/rest-api/#recommendtoanonymous
	 * @return array(item=>preference)
	 */
	public function recommendToAnonymous($data,$howmany="",$rescorerParams=""){
		//TODO:
		return false;
	}
	/**
	 *http://myrrix.com/rest-api/#mostsimilar
	 * @return array(item=>preference)
	 * @param array items
	 * @param id items	//id = most likely integer
	 */
	public function mostSimilar($items,$howmany="",$rescorerParams=""){
		$method="GET";
		$header=array('Accept: text/csv');
		$path="/similarity/";
		if(is_array($items)){
			foreach ($items as $value) {
				$path.=$value.'/';
			}
		}else {
			$path.=$items;
		}
		$path.='?howMany='.$howmany.'&rescorerParams='.$rescorerParams;
		$data=$this->request($path,$method,"",$header);
		return self::CSVtoArray($data);
	}
	/**
	 * http://myrrix.com/rest-api/#setusertag
	 * @return boolean
	 * @param id userid
	 * @param string tag	//mixed
	 */
	public function setUserTag($userid,$tag){
		$method="POST";
		$path="/tag/user/".$userid.'/'.$tag;
		return $this->call($path,$method);
	}
	/**
	 * http://myrrix.com/rest-api/#setitemtag
	 * @return boolean
	 * @param id itemid
	 * @param string tag	//mixed
	 */
	public function setItemTag($itemid,$tag){
		$method="POST";
		$path="/tag/item/".$itemid.'/'.$tag;
		return $this->call($path,$method);
	}
	/**
	 * http://myrrix.com/rest-api/#setaddpreference
	 * @return boolean
	 * @param id user
	 * @param id item
	 */
	public function setAddPreference($user,$item,$pref=1.0){
		$method="POST";
		$path="/pref/".$user.'/'.$item;
		return $this->call($path,$method,"",$pref);
	}
	/**
	 * http://myrrix.com/rest-api/#removepreference
	 * @return boolean
	 * @param id user
	 * @param id item
	 */
	public function removePreference($user,$item){
		$method="DELETE";
		$path="/pref/".$user.'/'.$item;
		return $this->call($path,$method);
	}
	/**
	 * http://myrrix.com/rest-api/#getalluserids
	 * @return array
	 */
	public function getAllUserIds(){
		$method="GET";
		$path="/user/allIDs";
		$header=array('Accept: text/csv');
		return self::CSVtoArray($this->request($path,$method,"",$header));
	}

	/**#@-*/


}


?>