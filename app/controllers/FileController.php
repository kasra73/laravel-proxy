<?php

class FileController extends BaseController {
    private $UID;
    private $errorStack;
    private $input=array();
    private $files=array();
    public function __construct()
    {
        $this->beforeFilter(function()
        {
            define('MULTIPART_BOUNDARY', '---laravel-proxy------'.microtime(true));
            if(Session::has('UID')){
                $this->UID=session::get('UID');
            }else{
                do{
                    $randID=rand(1000,9999).rand(1000,9999).rand(1000,9999).rand(1000,9999);
                    $randIDQuery = DB::table('cookies')->where('userID', $randID)->get();
                }while(count($randIDQuery)>0);
                $lifetime = time() + 60 * 60 * 24 * 365; // one year
                Config::set('session.lifetime', $lifetime);
                session::put('UID',$randID);
                $this->UID=$randID;
            }
        });
        ini_set( 'default_charset', 'UTF-8' );
    }
    //add system function
    private function getCoinUrl($URL)
    {
        $uuid = "1084abc45f7233bd05eb9a4ad515ec0c";
        $url = rawurlencode($URL);
        return "http://cur.lv/redirect.php?id=".$uuid."&url=".$url;
    }
    private function randomAds($url){
        return secure_url('encryptAds/'.Crypt::encrypt($url));
    }
    private function makeSafe($url,$address,$canAds=false){
        if(filter_var($url, FILTER_VALIDATE_URL)===false){
            if(substr($url,0,2)=='//'){
                $u=Crypt::encrypt(substr_replace($url,'http://',0,2));
            }elseif(substr($url,0,5)=='data:' || substr($url,0,1)=='#' || substr($url,0,5)=='javascript:'){
                return $url;
            }elseif(substr($url,0,1)=='/'){
                $u=Crypt::encrypt($this->siteHost($address).substr($url,1));
            }elseif(substr($url,0,2)=='./'){
                $u=Crypt::encrypt($address.substr($url,2));
            }else{
                $u=Crypt::encrypt($address.$url);
            }
        }else{
            $u=Crypt::encrypt($url);
        }
        if($canAds){
            return secure_url('encryptAds/'.$u);
        }else{
            return secure_url('encryptURL/'.$u);
        }
    }
    private function siteHost($url){
        $parsed=parse_url($url);
        $siteHost=$parsed['scheme'].'://'.$parsed['host'];
        $siteHost.= array_key_exists('port',$parsed) ? ':'.$parsed['port'].'/' : '/';
        return $siteHost;
    }

    private function makeStyleSafe($css,$address){
        //$oParser = new Sabberworm\CSS\Parser($css);
        $search = '/url\((\'*"*)(.*?)\1\)/';
        $css=preg_replace_callback($search,function($m) use ($address){
            return " url(".$this->makeSafe($m[2],$address).")";
        },$css);
        $search = '/@import(\'*"*)(.*?)\1/';
        $css=preg_replace_callback($search,function($m) use ($address){
            return " url(".$this->makeSafe($m[2],$address).")";
        },$css);

        return $css;
    }
    private function makeScriptSafe($script){
        /*$search =<<<EO
/(^|;|,|\(|{|\[|)(\w+)(\.src=)(?(?="|')((?:(?:\"(?:\\\\\"|[^\"])+\"))|(?:'(?:\\\'|[^'])+'))((?:\+(?:(?:(?:\"(?:\\\\\"|[^\"])+\"))|(?:'(?:\\\'|[^'])+')|\w+))*)|(.*?)($|;|\)|\]|,|}))/s
EO;
        return preg_replace_callback($search,function($m){
            if($m[4]){
                return $m[1]."laravelBBSRCencoder(".$m[2].",".$m[4].$m[5].")";
            }else{
                return $m[1]."laravelBBSRCencoder(".$m[2].",".$m[6].")".$m[7];
            }
        },$script);*/
        try{
            $safer=new JavaScript_Safer($script);
            return $safer->safe_script();
        }catch(Exception $ex){
            print $script;
            die();
        }
    }
    private function metaRefreshSafe($content,$address){
        $a=strstr($content,',',true);
        $a=($a=='')?'0':$a;
        return $a.',url='.$this->makeSafe(substr(stristr($content,'url='),4),$address);
    }
    private function makeLinksSafe(&$html,$address){
        //print_r($html);die();
        /*include_once(app_path().'/library/htmlEvents.php');
        $scriptSafer='
<script src="/laravelProxyJavaScriptSaferBefore.js"></script>
<script>var laravelBBthisPageaddress=Base64.decode("'.base64_encode($address)."\");\n".
'var laravelBBhost="https://"+Base64.decode("'.base64_encode($_SERVER['HTTP_HOST']).'");'.'
</script>
<script src="/laravelProxyJavaScriptSaferPHP.js"></script>
';*/
        foreach($html('*[src]') as $element)
            $element->src=$this->makeSafe($element->src,$address);
        foreach($html('video[poster]') as $element)
            $element->poster=$this->makeSafe($element->poster,$address);
        foreach($html('object[data]') as $element)
            $element->poster=$this->makeSafe($element->poster,$address);
        
        foreach($html('*[href]') as $element){
            if($element->getTag()=='a'){
                $element->href=$this->makeSafe($element->href,$address,true);
            }else{
                $element->href=$this->makeSafe($element->href,$address);
            }
        }
        foreach($html('*[background]') as $element)
            $element->background=$this->makeSafe($element->background,$address);
        foreach($html('form[action]') as $element)
            $element->action=$this->makeSafe($element->action,$address);
        foreach($html('style') as $element)
            $element->setPlainText($this->makeStyleSafe($element->getPlainText(),$address));
        foreach($html('*[style]') as $element)
            $element->style=$this->makeStyleSafe($element->style,$address);
        foreach($html('meta[http-equiv=refresh]') as $element)
            $element->content=$this->metaRefreshSafe($element->content,$address);
        /*foreach($html('script:not-empty > "~text~"') as $element){
            //file_put_contents("script.txt",$element->text."\n--------------------\n",FILE_APPEND);
            //$minifyed=JSMinPlus::minify($element->text);
            //file_put_contents("script.txt",$minifyed."\n----------------------\n",FILE_APPEND);
            $element->text=$this->makeScriptSafe($element->text);
            //file_put_contents("script.txt",$element->text."\n\n______________________\n",FILE_APPEND);
        }
        foreach($JavaScriptAttrs as $attr){
            foreach($html("*[$attr]") as $element){
                $element->{$attr}=$this->makeScriptSafe(html_entity_decode($element->{$attr},ENT_QUOTES | ENT_HTML401));
            }
        }
        $head=$html('head',0);
        if($head!=NULL){
            $head->setInnertext($scriptSafer.$head->getInnertext());
        }
        dom_format($html,array('minify_script' => FALSE));*/
        //with simple_html_dom 
        /*foreach($html->find('*[src]') as $element)
            $element->src=$this->makeSafe($element->src,$address);
        foreach($html->find('video[poster]') as $element)
            $element->poster=$this->makeSafe($element->poster,$address);
        foreach($html->find('object[data]') as $element)
            $element->poster=$this->makeSafe($element->poster,$address);
        foreach($html->find('*[href]') as $element){
            $element->href=$this->makeSafe($element->href,$address);
        }
        foreach($html->find('*[background]') as $element){
            $element->background=$this->makeSafe($element->background,$address);
        }
        foreach($html->find('form[action]') as $element)
            $element->action=$this->makeSafe($element->action,$address);
        foreach($html->find('style') as $element){
            $element->innertext=$this->makeStyleSafe($element->innertext,$address);
        }
        foreach($html->find('*[style]') as $element){
            $element->style=$this->makeStyleSafe($element->style,$address);
        }
        foreach($html->find('meta[http-equiv=refresh]') as $element){
            $element->content=$this->metaRefreshSafe($element->content,$address);
        }*/

        //disable scripts
        foreach($html('script') as $element){
            $element->setOuterText('');
        }
        foreach($html('noscript') as $element){
            $element->setOuterText($element->getInnerText());
        }
        include(app_path().'/storage/htmlEvents.php');
        foreach($JavaScriptAttrs as $attr){
            foreach($html('*['.$attr.']') as $element){
                $element->{$attr}='';
            }
        }
	    /*foreach($html('script:not-empty > "~text~"') as $element){
            file_put_contents("script.txt",$element->innertext."\n--------------------\n",FILE_APPEND);
            $minifyed=\JShrink\Minifier::minify($element->innertext);
            file_put_contents("script.txt",$minifyed."\n----------------------\n",FILE_APPEND);
            $element->innertext=$this->makeScriptSafe($element->innertext);
            file_put_contents("script.txt",$element->innertext."\n\n______________________\n",FILE_APPEND);
        }
        foreach($JavaScriptAttrs as $attr){
            foreach($html->find("*[$attr]") as $element){
                $element->{$attr}=$this->makeScriptSafe(\JShrink\Minifier::minify(html_entity_decode($element->{$attr},ENT_QUOTES | ENT_HTML401)));
            }
        }
        $head=$html->find('head',0);
        if($head!=NULL){
            $head->innertext=$scriptSafer.$head->innertext;
        }*/
    }


    private function setCookies($cookies=false,$url){
        if(is_array($cookies)){
            foreach($cookies as $cookie){
                $this->saveCookie($cookie,$url);
            }
        }else{
            $this->saveCookie($cookies,$url);
        }
    }
    /*private function array_add_qoutes(&$array){
        function add_qoutes(&$item){
            if(is_string($item))
                $item = "'".$item."'";
            elseif($item===NULL)
                $item='NULL';
        }
        array_walk($array,'add_qoutes');
    }*/
    private function updateIfExist($data){
        $cookies=$this->getCookies($data['domain'],$data['path']);
        foreach($cookies as $cookie){
            if($data['key']==$cookie->key){

                if($cookie->expires>0 && $data['expires']==0)
                    $data['expires']=$cookie->expires;

                DB::table('cookies')->where('id',$cookie->id)->update($data);
                return true;
            }
        }
        return false;
    }

    private function saveCookie($cookie,$url){
        $data=$this->cookie_parse($cookie);
        if(!array_key_exists('domain',$data)){
            $data['domain']=$url['host'];
            if(!array_key_exists('path',$data)){
                $data['path']='/';
            }
        }else{
            if(substr_count($data['domain'],'.')>1){
                if(substr($data['domain'],0,1)!='.'){
                    $data['domain']='.'.$data['domain'];
                }
            }else if(substr($data['domain'],0,1)=='.'){
                return;
            }
        }
        $data['userID']=$this->UID;
        $isUpdate=$this->updateIfExist($data);
        $data['ID']=null;
        ksort($data);
        if(!$isUpdate){
            DB::table('cookies')->insert($data);
        }

    }
    private function getCookies($domain,$path='/',$httpOnly=false){
        $cookies=DB::table('cookies')->where('userID','=',$this->UID)->where('domain','Like',$domain)->get();
        while(true){
            if(($dotPosition=strpos($domain,'.',1))===false)break;
            $domain=substr($domain,$dotPosition);
            $cookies=array_merge($cookies,DB::table('cookies')->where('userID','=',$this->UID)->where('domain','Like',$domain)->get());
        }
        for($i=0;$i<count($cookies);$i++){
            date_default_timezone_set("GMT");
            if($cookies[$i]->expires!=0 && $cookies[$i]->expires<time()){
                DB::table('cookies')->where('id',$cookies[$i]->id)->delete();
                unset($cookies[$i]);
            }
        }
        return $cookies;
    }

    private function makeDefaultHeader(&$urll){
        $url=parse_url($urll);
        if(array_key_exists('path',$url)){
            $cookies=$this->getCookies($url['host'],$url['path']);
        }else{
            $cookies=$this->getCookies($url['host']);
        }

        $cookieString="";
        if(count($cookies)>0){
            $cookieString="\r\nCookie:";
            foreach($cookies as $cookie){
                $cookieString.= " ".$cookie->key."=".$cookie->value.";";
            }
        }
        //print $cookieString;        
        //$IE='Mozilla/5.0 (Windows NT 6.1; rv:12.0) Gecko/20120403211507 Firefox/12.0';
        $IE=$_SERVER['HTTP_USER_AGENT'];
        //$IE='Mozilla/5.0 (Linux; U; Android 4.0.3; ko-kr; LG-L160L Build/IML74K) AppleWebkit/534.30 (KHTML, like Gecko) Version/4.0 Mobile Safari/534.30';
        //$IE='Opera/9.80 (J2ME/MIDP; Opera Mini/4.4.29476/27.1573; U; id) Presto/2.8.119 Version/11.10';
        $default_opts = array(
          'http'=>array(
            'method'=>"GET",
            'header'=>"Accept-language: en" .
                    "Accept: */*".
                    "Accept-Encoding: gzip,deflate".
                    $cookieString.
                    "\r\nUser-Agent: ".$IE,
            'follow_location'=>'0',
            'ignore_errors' => true
          )
        );
        if(array_key_exists('Orgin',$_SERVER)){
            $default_opts['http']['header'].="\r\nOrgin: ".$url['scheme'].'://'.$url['host'];
           if(array_key_exists('port',$url)){
                $default_opts['http']['header'].=":".$url['port'];
           }
        }
        
        $data=FALSE;
        if(count($this->files)>0){

            $default_opts['http']['method']="POST";
            $default_opts['http']['header'].="\r\nContent-Type: multipart/form-data; boundary=".MULTIPART_BOUNDARY;
            $content='';
            foreach($this->files as $key=>$file){
                $file_contents=file_get_contents($file->getRealPath());
                $content .=  "--".MULTIPART_BOUNDARY."\r\n".
                "Content-Disposition: form-data; name=\"".urlencode($key)."\"; filename=\"".urlencode($file->getClientOriginalName())."\"\r\n".
                "Content-Type: ".$file->getClientMimeType()."\r\n\r\n".
                $file_contents."\r\n";
            }
            if(count($this->input['POST'])>0){
                $data=explode('&',$this->executeQuery($this->input['POST']));
                // add some POST fields to the request too: $_POST['foo'] = 'bar'
                foreach($data as $query){
                $content .= "--".MULTIPART_BOUNDARY."\r\n".
                            "Content-Disposition: form-data; name=\"".strstr($query,'=',true)."\"\r\n\r\n".
                            "".urldecode(substr(strstr($query,'='),1))."\r\n";
                }
            }
            // signal end of request (note the trailing "--")
            $content .= "--".MULTIPART_BOUNDARY."--\r\n";
            $default_opts['http']['content']=$content;
        }elseif(array_key_exists('POST',$this->input)){

            $data=$this->executeQuery($this->input['POST']);

            $default_opts['http']['method']="POST";
            $default_opts['http']['header'].="\r\nContent-Type: application/x-www-form-urlencoded";
            $default_opts['http']['header'].="\r\nContent-length: ".strlen($data);
            $default_opts['http']['content']=$data;
        }elseif(array_key_exists('GET',$this->input)){

            $data=$this->executeQuery($this->input['GET']);
            if(array_key_exists('query',$url)){
                $url['query'].='&'.$data;
            }else{
                $url['query']=$data;
            }
        }
        $default = stream_context_set_default($default_opts);
        $outURL=$url['scheme'].'://';
        if(array_key_exists('user',$url)){
            $outURL.=$url['user'];
            if(array_key_exists('pass',$url))
                $outURL.=':'.$url['pass'];
            $outURL.='@';
        }
        $outURL.=urlencode($url['host']);
        if(array_key_exists('port',$url))
            $outURL.=':'.$url['port'];
        if(array_key_exists('path',$url))
            $outURL.=$url['path'];
        if(array_key_exists('query',$url))
            $outURL.='?'.$url['query'];
        $urll=$outURL;
        return $default_opts['http']['method'];
    }

    private function executeQuery($queryArray,&$outQuery=null,&$counter=null,$oldkey=''){
        if($counter===null)$counter=0;
        if($outQuery===null)$outQuery=array();
        foreach($queryArray as $key=>$val){
                    if(is_array($val)){
                        if($oldkey==''){
                            $this->executeQuery($val,$outQuery,$counter,$key);
                        }else{
                            $this->executeQuery($val,$outQuery,$counter,$oldkey.'['.$key.']');
                        }
                    }elseif($oldkey==''){
                        $outQuery[$counter]=urlencode($key)."=".urlencode($val);
                    }else{
                        $outQuery[$counter]=urlencode($oldkey.'['.$key.']')."=".urlencode($val);
                    }
                    $counter++;
                }
            return implode('&',$outQuery);
    }

    private function cookie_parse( $line) {
        $csplit = explode( ';', $line );
        $cdata = array();
        $cdata['httponly']=0;
        $cdata['expires']=0;
        $cdata['secure']=0;
        $cdata['path']='/';
        $cdata['comment']='';
        foreach( $csplit as $data ) {
            $cinfo =array();
            
            if(strpos($data,'=')===false){
                $cinfo[0]=$data;
            }else{
                $cinfo[0]=strstr($data,'=',true);
                $cinfo[1]=substr(strstr($data,'='),1);
            }

            $cinfo[0] = trim( $cinfo[0] );
            $cifoLower=strtolower($cinfo[0]);
            if( $cifoLower == 'expires' ) $cinfo[1] = strtotime( $cinfo[1] );
            if( $cifoLower == 'secure' ) $cinfo[1] = 1;
            if( $cifoLower == 'httponly' ) $cinfo[1] = 1;
            if( in_array( $cifoLower, array( 'domain', 'expires', 'httponly', 'path', 'secure', 'comment' ) ) ) {
                $cdata[$cifoLower] = $cinfo[1];
            }elseif($cifoLower=='priority' || $cifoLower=='max-age') {
            }else{
                $cdata['key'] = $cinfo[0];
                $cdata['value'] = urldecode($cinfo[1]);
            }
        }
        return $cdata;
    }
    private function makePageAddress($url){
        $URLparsed=parse_url($url);
        $outURL=$URLparsed['scheme']."://";
        if(array_key_exists('username',$URLparsed)){
            $outURL.=$URLparsed['username'].":";
        }
        if(array_key_exists('password',$URLparsed)){
            $outURL.=$URLparsed['password']."@";
        }
        $outURL.=urlencode($URLparsed['host']);
        if(array_key_exists('port',$URLparsed)){
            $outURL.=":".$URLparsed['port'];
        }
        if(array_key_exists('path',$URLparsed)){
            $outURL.=substr($URLparsed['path'],0,strrpos($URLparsed['path'],'/')+1);
        }else{
            $outURL.='/';
        }
        return $outURL;
    }

    private function url_encode_path($url){
        $e=explode('/',$url);
        array_walk($e,function(&$item){
            $item=urlencode($item);
        });
        return implode('/',$e);
    }
    private function url_encode_query($query){
        $query=htmlspecialchars_decode($query);
        return $query;
        $exp=explode('&',$query);
        array_walk($exp,function(&$val){
            if(strpos('=',$val)!==false){
                $key=strstr($val,'=',true);
                $value=substr(strstr($val,'='),1);
                $val=$key.'='.$value;
            }
        });
        return implode('&',$exp);
    }
    private function url_encode_all($url) { 
        $parsed_url=parse_url($url);
        $scheme   = isset($parsed_url['scheme']) ? urlencode($parsed_url['scheme']) . '://' : ''; 
        $host     = isset($parsed_url['host']) ? urlencode($parsed_url['host']) : ''; 
        $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : ''; 
        $user     = isset($parsed_url['user']) ? $parsed_url['user'] : ''; 
        $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : ''; 
        $pass     = ($user || $pass) ? "$pass@" : ''; 
        $path     = isset($parsed_url['path']) ? $this->url_encode_path($parsed_url['path']) : ''; 
        $query    = isset($parsed_url['query']) ? '?' . $this->url_encode_query($parsed_url['query']) : ''; 
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : ''; 
        return "$scheme$user$pass$host$port$path$query$fragment"; 
    } 
    
    private function lableHeaders($fields){
        $retVal = array();
        foreach( $fields as $field ) {
            if( preg_match('/([^:]+): (.+)/m', $field, $match) ) {
                $match[1] = preg_replace_callback('/(?<=^|[\x09\x20\x2D])./',function($m){
                   return strtoupper($m[0]);
                }, strtolower(trim($match[1])));
                if( isset($retVal[$match[1]]) ) {
                    if (!is_array($retVal[$match[1]])) {
                        $retVal[$match[1]] = array($retVal[$match[1]]);
                    }
                    $retVal[$match[1]][] = $match[2];
                } else {
                    $retVal[$match[1]] = trim($match[2]);
                }
            }
        }
        return $retVal;
    }

    public function showFile($url)
    {
        $method=$this->makeDefaultHeader($url);
        $file=file_get_contents($url);
        $headers=$this->lableHeaders($http_response_header);
        //print_r($headers);
        if(array_key_exists('Set-Cookie',$headers)){
            $this->setCookies($headers['Set-Cookie'],parse_url($url));
        }
        if(array_key_exists('Location',$headers)){
            $pageaddress=$this->makePageAddress($url);
            $url=(is_array($headers['Location']))?array_pop($headers['Location']):$headers['Location'];
            $url=$this->url_encode_all($url);
            $p=parse_url($url);
            if(!array_key_exists('host',$p)){
                $url=$pageaddress.$url;
            }
            return Redirect::secure('encryptURL/'.Crypt::encrypt($url));
        }
        /*while(array_key_exists('Location',$headers)){
            $pageaddress=$this->makePageAddress($url);
            $url=(is_array($headers['Location']))?array_pop($headers['Location']):$headers['Location'];
            $url=$this->url_encode_all($url);
            $p=parse_url($url);
            if(!array_key_exists('host',$p)){
                $url=$pageaddress.$url;
            }
            $context=$this->makeDefaultHeader($url);
            //print "\n".$url."\n";
            $file=file_get_contents($url);
            //print htmlspecialchars($file)."<hr>";
            $headers=$this->lableHeaders($http_response_header);
            //print_r($headers);
            if(array_key_exists('Set-Cookie',$headers)){
                $this->setCookies($headers['Set-Cookie'],parse_url($url));
            }
        }*/
        $pageaddress=$this->makePageAddress($url);
        if(!array_key_exists('Content-Type',$headers)){
            $headers['Content-Type']='text/html';
        }
        if(strpos($headers['Content-Type'],'text/html')!==false || strpos($headers['Content-Type'],'application/xhtml')!==false){
            //include_once(app_path().'/library/simple_html_dom.php');
            include_once(app_path().'/library/ganon.php');
            if($o = str_get_dom($file)){
                $this->makeLinksSafe($o,$pageaddress);
                $file=''.$o;
            }
        }elseif(strpos($headers['Content-Type'],'text/css')!==false){
            $file=$this->makeStyleSafe($file,$pageaddress);
        }elseif(strpos($headers['Content-Type'],'text/javascript')!==false){
	    include(app_path().'/library/Minifier.php');
	    $file=$this->makeScriptSafe($file,$pageaddress);
	    //$file=$this->makeScriptSafe(\JShrink\Minifier::minify($file),$pageaddress);
        }
        $response = Response::make($file, 200);
        $response->header('Content-Type',$headers['Content-Type']);
        return $response;
    }
    
    public function loadPage($url) {
        if(count(Input::all())>0){
            $allInput=Input::all();
            array_walk($allInput,function(&$val,$key,&$input){
                if(Input::hasFile($key)){
                    $this->files[$key]=Input::file($key);
                    unset($input[$key]);
                }
            },$allInput);
            $this->input[Input::method()]=$allInput;
        }
        
        $url=Crypt::decrypt($url);
        $url=$this->url_encode_all($url);
        if(filter_var($url, FILTER_VALIDATE_URL)===false)
            return $url."<br>";
        else 
            return $this->showFile($url);
    }
    public function loadPageAds($url){
        $url=Crypt::decrypt($url);
        $url=$this->url_encode_all($url);
        if(filter_var($url, FILTER_VALIDATE_URL)===false)
            return $url."<br>";
        else{
            $r=rand(10,85);
            if($r>50 && $r<60){
                return link_to($this->getCoinUrl(secure_url('encryptURL/'.Crypt::encrypt($url))),"View this link to continue! :D");
            }else{
                return $this->showFile($url);
            }
        }
    }
    public function directController($url){
        $url=$this->url_encode_all($url);
        if(filter_var($url, FILTER_VALIDATE_URL)===false)
            return $url;
        else 
            return $this->showFile($url);
    }
    
    public function base64loader($url){
        if(count(Input::all())>0){
            $allInput=Input::all();
            array_walk($allInput,function(&$val,$key,&$input){
                if(Input::hasFile($key)){
                    $this->files[$key]=Input::file($key);
                    unset($input[$key]);
                }
            },$allInput);
            $this->input[Input::method()]=Input::all();
        }
        $url=base64_decode($url);
        $url=$this->url_encode_all($url);
        if(filter_var($url, FILTER_VALIDATE_URL)===false)
            return $url;
        else 
            return $this->showFile($url);
    }
    public function goInput(){
        if(Input::has('url')){
            $url=Input::get('url');
            $Q=parse_url($url);
            if(array_key_exists('scheme',$Q)){
                $url=$this->url_encode_all($url);
                return Redirect::secure('encryptURL/'.Crypt::encrypt($url));
            }else{
                $url='http://'.$url;
                $url=$this->url_encode_all($url);
                return Redirect::secure('encryptURL/'.Crypt::encrypt($url));
            }
        }
        Redirect::secure('/');
    }
}
