<?php
/**
* 
*/
class JavaScript_Safer {

// ----------------------------------------------------------------------------
//  Properties
	
	private $text            = null;
	private $tokens          = null;
	private $pos             = 0;
	private $tokpos          = 0;
	private $line            = 0;
	private $tokline         = 0;
	private $col             = 0;
	private $tokcol          = 0;
	private $newline_before  = false;
	private $regex_allowed   = false;
	private $comments_before = array();
	private $tokenCounter    = 0;
	private $lookFor         = array(array('punc','.'),array('name','src'),array('operator','='));
// ----------------------------------------------------------------------------
//  Public functions
	
	public function __construct($input) {
		$input = preg_replace('/\r\n?|[\n\x{2028}\x{2029}]/u', "\n", $input);
		$input = preg_replace('/^\x{FEFF}/u', '', $input);
		$this->text = $input;
	}

	public function safe_script() {
		$tokens =&$this->tokens;
		$tokens = array();
		$shouldChange=array();
		for (;;) {
			$tokens[] = $this->next_token();
			if(ParseJS::is_token($tokens[count($tokens) - 1], 'punc','.')){
				$i=count($tokens)-1;
				$tokens[] = $this->next_token();
				if(ParseJS::is_token($tokens[$i+1], 'name')){
					if(in_array($tokens[$i+1]['v'],ParseJS::$CHANGE_FIELDS)){
						$tokens[] = $this->next_token();
						if(ParseJS::is_token($tokens[$i+2], 'operator','=')){
							$shouldChange[]=array(
									'p'=>$tokens[$i]['p'],
									'o'=>$tokens[$i+2]['p'],
									's'=>$this->selectBefore($i),
									'e'=>$this->selectAfter($i),
									't'=>$tokens[$i+1]['v']
								);
						}elseif(!ParseJS::is_token($tokens[$i+2], 'operator','(')){
						    $justChange[]=array(
									'p'=>$tokens[$i]['p'],
									//'o'=>$tokens[$i+2]['p'],
									's'=>$this->selectBefore($i),
									't'=>$tokens[$i+1]['v']
								);
						}
					}elseif(in_array($tokens[$i+1]['v'],ParseJS::$CHANGE_FUNCS)){
					    $tokens[] = $this->next_token();
						if(ParseJS::is_token($tokens[$i+2], 'punc','(')){
                            $inside=$this->selectInsideFunc($i);
                            if($inside!==false){
							    $shouldChange[]=array(
									    'p'=>$tokens[$i]['p'],
									    'o'=>$tokens[$i+2]['p'],
									    's'=>$this->selectBefore($i),
									    'e'=>$inside,
									    't'=>$tokens[$i+1]['v']
								    );
                            }
						}
					}
				}
			}
			while(count($tokens)>50){
				unset($tokens[0]);
				$tokens=array_values($tokens);
			}
			if (ParseJS::is_token($tokens[count($tokens) - 1], 'eof')) break;
		}
		$changed=0;
        foreach ($shouldChange as $value) {
		}
		foreach ($shouldChange as $value) {
			$var=substr($this->text,$value['s']+$changed,$value['p']-$value['s']);
			$val=substr($this->text,$value['o']+1+$changed,$value['e']-$value['o']-1);
			$this->text=substr_replace($this->text,"laravelBBSRCencoder($var,'".$value['t']."',$val)",$value['s']+$changed,$value['e']-$value['s']);
			$changed+=23;
		}
		return $this->text;
	}

	private function selectBefore($index){
		$n=$index-1;
		$inBracket=0;
		for(;$n>0;$n--){
 			if($inBracket){
				if(ParseJS::is_token($this->tokens[$n], 'punc','(')){
					$inBracket--;
					continue;
				}elseif(ParseJS::is_token($this->tokens[$n], 'punc',')')){
					$inBracket++;
					continue;
				}else{
					continue;
				}
			}
			if(ParseJS::is_token($this->tokens[$n], 'name')){
				continue;
			}
			if(ParseJS::is_token($this->tokens[$n], 'operator','new')){
				continue;
			}
			if(ParseJS::is_token($this->tokens[$n], 'punc','.')){
				continue;
			}
			if(ParseJS::is_token($this->tokens[$n], 'punc',')') && ParseJS::is_token($this->tokens[$n+1], 'punc','.')){
				$inBracket++;
				continue;
			}
			break;
		}
		return $this->tokens[++$n]['p'];
	}
    
	private function selectInsideFunc($index){
		$n=$index+3;
		$inBracket=0;
		$tokens=&$this->tokens;
		$inCondition=0;
		for(;;$n++){
			$tokens[]=$this->next_token();
                print_r($tokens[$n]);
                print "\n";
			if($inBracket){
				if(ParseJS::is_token($tokens[$n], 'punc',')')){
					$inBracket--;
					continue;
				}elseif(ParseJS::is_token($tokens[$n], 'punc','(')){
					$inBracket++;
					continue;
				}else{
					continue;
				}
			}
			if(ParseJS::is_token($tokens[$n], 'string')){
				continue;
			}
			if(ParseJS::is_token($tokens[$n], 'regexp')){
				continue;
			}
			if(ParseJS::is_token($tokens[$n], 'name')){
				continue;
			}
			if(ParseJS::is_token($tokens[$n], 'operator')){
				if($tokens[$n]['v']=='?')$inCondition++;
				continue;
			}
			if(ParseJS::is_token($tokens[$n], 'punc',':') && $inCondition){
				$inCondition--;
				continue;
			}
			if(ParseJS::is_token($tokens[$n], 'punc','(')){
				$inBracket++;
				continue;
			}
			if(ParseJS::is_token($tokens[$n], 'punc',',')){
				return false;
			}
			if(ParseJS::is_token($tokens[$n], 'punc',')')){
				break;
			}
		}
		return $tokens[$n]['p'];
	}

	private function selectAfter($index){
		$n=$index+3;
		$inBracket=0;
		$tokens=&$this->tokens;
		$inCondition=0;
		for(;;$n++){
			$tokens[]=$this->next_token();
			if($inBracket){
				if(ParseJS::is_token($tokens[$n], 'punc',')')){
					$inBracket--;
					continue;
				}elseif(ParseJS::is_token($tokens[$n], 'punc','(')){
					$inBracket++;
					continue;
				}else{
					continue;
				}
			}
			if(ParseJS::is_token($tokens[$n], 'string')){
				continue;
			}
			if(ParseJS::is_token($tokens[$n], 'regexp')){
				continue;
			}
			if(ParseJS::is_token($tokens[$n], 'name')){
				continue;
			}
			if(ParseJS::is_token($tokens[$n], 'operator')){
				if($tokens[$n]['v']=='?')$inCondition++;
				continue;
			}
			if(ParseJS::is_token($tokens[$n], 'punc','.')){
				continue;
			}
			if(ParseJS::is_token($tokens[$n], 'punc',':') && $inCondition){
				$inCondition--;
				continue;
			}
			if(ParseJS::is_token($tokens[$n], 'punc','(')){
				$inBracket++;
				continue;
			}
			break;
		}
		return $tokens[$n]['p'];
	}

	private function get_tokens() {
		if (! $this->tokens) {
			$this->tokens = $this->tokenize();
		}
		return $this->tokens;
	}

	private function next_token($force_regexp = false) {
	
		if ($force_regexp) {
			return $this->read_regexp();
		}
		$this->skip_whitespace();
		$this->start_token();
		$ch = $this->peek();
		if ( $ch===null ) {
			return $this->token('eof');
		}
		if (ParseJS::is_digit($ch)) {
			return $this->read_num();
		}
		if ($ch == '"' || $ch == "'") {
			return $this->read_string();
		}
		if (in_array($ch, ParseJS::$PUNC_CHARS)) {
			return $this->token('punc', $this->next());
		}
		if ($ch == '.') {
			return $this->handle_dot();
		}
		if ($ch == '/') {
			return $this->handle_slash();
		}
		if (in_array($ch, ParseJS::$OPERATOR_CHARS)) {
			return $this->read_operator();
		}
		if ($ch == '\\' || ParseJS::is_identifier_char($ch)) {
			return $this->read_word();
		}
		return $this->parse_error("Unexpected character '${ch}'");
	}
	
// ----------------------------------------------------------------------------
//  Internal helper functions

	private function raise($msg, $line, $col, $pos) {
		throw new JS_Parse_Error($msg, $line, $col, $pos);
	}

	private function parse_error($msg) {
		return $this->raise($msg, $this->line, $this->col, $this->pos);
	}

	private function peek() {
		return ((isset($this->text[$this->pos])) ? $this->text[$this->pos] : null);
	}

	private function next($signal_eof = false) {
		$ch = $this->text[$this->pos++];
		if ($signal_eof && $ch===null) {
			throw new JS_EOF();
		}
		if ($ch == "\n") {
			$this->newline_before = true;
			$this->line++;
			$this->col = 0;
		} else {
			$this->col++;
		}
		return $ch;
	}

	private function eof() {
		return (! $this->peek());
	}

	private function find($what, $signal_eof = null) {
		$pos = strpos($this->text, $what, $this->pos);
		if ($signal_eof && $pos === false) throw new JS_EOF();
		return $pos;
	}

	private function start_token() {
		$this->tokline = $this->line;
		$this->tokcol  = $this->col;
		$this->tokpos  = $this->pos;
	}

	private function token($type, $value = null, $is_comment = false) {
		$this->regex_allowed = (
			($type == 'operator' && ! in_array($value, ParseJS::$UNARY_POSTFIX)) ||
			($type == 'keyword' && in_array($value, ParseJS::$KEYWORDS_BEFORE_EXPRESSION)) ||
			($type == 'punc' && in_array($value, ParseJS::$PUNC_BEFORE_EXPRESSION))
		);
		//$ret = new JS_Token($type, $value, $this->tokline, $this->tokcol, $this->tokpos, $this->newline_before);
		$ret=array('t'=>$type,'v'=>$value,'p'=>$this->tokpos);
		if (! $is_comment) {
			//$ret->comments_before = $this->comments_before;
			$this->comments_before = array();
		}
		$this->newline_before = false;
		//file_put_contents("regexp.txt",json_encode($ret)."\n",FILE_APPEND);
		return $ret;
	}

	private function skip_whitespace() {
		while (in_array($this->peek(), ParseJS::$WHITESPACE_CHARS)) {
			$this->next();
		}
	}

	private function read_while($pred) {
		$i = 0;
		$ret = '';
		$ch = $this->peek();
		while ($ch!==null && $pred($ch, $i)) {
			$ret .= $this->next();
			$ch = $this->peek();
            $i++;
		}
		return $ret;
	}

	private function read_num($prefix = '') {
		$has_e = false;
		$after_e = false;
		$has_x = false;
		$has_dot = ($prefix == '.');
		$num = $this->read_while(function($ch, $i) use(&$has_e, &$after_e, &$has_x, &$has_dot,$prefix) {
			if ($ch == 'x' || $ch == 'X') {
				if ($has_x) return false;
				return ($has_x = true);
			}
			if (! $has_x && ($ch == 'e' || $ch == 'E')) {
				if ($has_e) return false;
				return ($has_e = $after_e = true);
			}
			if ($ch == '-') {
				if ($after_e || ($i == 0 && ! $prefix)) return true;
				return false;
			}
			if ($ch == '+') return $after_e;
			$after_e = false;
			if ($ch == '.') {
				if (! $has_dot && ! $has_x) {
					return ($has_dot = true);
				}
				return false;
			}
			return ParseJS::is_alphanumeric_char($ch);
		});
		if ($prefix) {
			$num = $prefix.$num;
		}
        if($num[strlen($num)-1]==='-' || $num[strlen($num)-1]==='+'){
            $num=substr($num,0,strlen($num)-1);
        }
		$valid = ParseJS::parse_js_number($num);
		if (is_numeric($valid)) {
			return $this->token('num', $valid);
		} else {
			return $this->parse_error('Invalid syntax: '.$num);
		}
	}

	private function read_escaped_char() {
		$ch = $this->next(true);
		switch ($ch) {
			case 'n': return "\n";
			case 'r': return "\r";
			case 't': return "\t";
			case 'b': return "\b";
			case 'v': return "\v";
			case 'f': return "\f";
			case '0': return "\0";
			case 'x': return ParseJS::unichr($this->hex_bytes(2));
			case 'u': return ParseJS::unichr($this->hex_bytes(4));
			default:  return $ch;
		}
	}

	private function hex_bytes($n) {
		$num = 0;
		for (; $n > 0; --$n) {
			$digit = intval($this->next(true), 16);
			if (! is_numeric($digit)) {
				return $this->parse_error('Invalid hex character pattern in string');
			}
			$num = ($num << 4) | $digit;
		}
		return $num;
	}

	private function read_string() {
		$self =& $this;
		return $this->with_eof_error('Unterminated string constant', function() use(&$self) {
			$quote = $self->next();
			$ret = '';
			for (;;) {
				$ch = $self->next(true);
				if ($ch == '\\') {
					$ch = $self->read_escaped_char();
				} elseif ($ch == $quote) {
					break;
				}
				$ret .= $ch;
			}
			return $self->token('string', $ret);
		});
	}

	private function substr($str, $start, $end = null) {
		if ($end === null) $end = strlen($str);
		return substr($str, $start, $end - $start);
	}

	private function read_line_comment() {
		$this->next();
		$i = $this->find("\n");
		if ($i === false) {
			$ret = $this->substr($this->text, $this->pos);
			$this->pos = strlen($this->text);
		} else {
			$ret = $this->substr($this->text, $this->pos, $i);
			$this->pos = $i;
		}
		return $this->token('comment1', $ret, true);
	}

	private function read_multiline_comment() {
		$this->next();
		$self =& $this;
		return $this->with_eof_error('Unterminated multiline comment', function() use(&$self) {
			$i = $self->find('*/', true);
			$text = $self->substr($self->text, $self->pos, $i);
			$tok = $self->token('comment2', $text, true);
			$self->pos = $i + 2;
			$self->line += count(explode("\n", $text)) - 1;
			$self->newline_before = (strpos($text, "\n") !== false);
			return $tok;
		});
	}

	private function read_name() {
		$backslash = false;
		$name = '';
		while (($ch = $this->peek()) !== null) {
			if (! $backslash) {
				if ($ch == '//') {
					$backslash = true;
					$this->next();
				} elseif (ParseJS::is_identifier_char($ch)) {
					$name .= $this->next();
				} else {
					break;
				}
			} else {
				if ($ch != 'u') {
					return $this->parse_error('Expecting UnicodeEscapeSequence -- uXXXX');
				}
				$ch = $this->read_escaped_char();
				if (! ParseJS::is_identifier_char($ch)) {
					return $this->parse_error('Unicode char: '.ParseJS::uniord($ch).' is not valid in identifier');
				}
				$name .= $ch;
				$backslash = false;
			}
		}
		return $name;
	}

	private function read_regexp() {
		$self =& $this;
		return $this->with_eof_error('Unterminated regular expression', function() use(&$self) {
			$prev_backslash = false;
			$regexp = '';
			$in_class = false;
			while (($ch = $self->next(true))!==null) {
				if ($prev_backslash) {
					$regexp .= '\\'.$ch;
					$prev_backslash=false;
				} elseif ($ch == '[') {
					$in_class = true;
					$regexp .= $ch;
				} elseif ($ch == ']' && $in_class) {
					$in_class = false;
					$regexp .= $ch;
				} elseif ($ch == '/' && ! $in_class) {
					break;
				} elseif ($ch == '\\') {
					$prev_backslash = true;
				} else {
					$regexp .= $ch;
				}
			}
			$mods = $self->read_name();
			return $self->token('regexp', array($regexp, $mods));
		});
	}

	private function read_operator($prefix = null) {
		$self =& $this;
		$grow = function($op) use(&$self,&$grow) {
			if (! $self->peek()) return $op;
			$bigger = $op.$self->peek();
			if (in_array($bigger, ParseJS::$OPERATORS)) {
				$self->next();
				return $grow($bigger);
			} else {
				return $op;
			}
		};
		$value = ($prefix) ? $prefix : $this->next();
		return $this->token('operator', $grow($value));
	}

	private function handle_slash() {
		$this->next();
		$regex_allowed = $this->regex_allowed;
		switch ($this->peek()) {
			case '/':
				$this->comments_before[] = $this->read_line_comment();
				$this->regex_allowed = $regex_allowed;
				return $this->next_token();
			break;
			case '*':
				$this->comments_before[] = $this->read_multiline_comment();
				$this->regex_allowed = $regex_allowed;
				return $this->next_token();
			break;
		}
		return (($this->regex_allowed) ? $this->read_regexp() : $this->read_operator("/"));
	}

	private function handle_dot() {
		$this->next();
		return (ParseJS::is_digit($this->peek()) ?
			$this->read_num('.') :
			$this->token('punc', '.'));
	}

	private function read_word() {
		$word = $this->read_name();
		if (! in_array($word, ParseJS::$KEYWORDS)) {
			return $this->token('name', $word);
		} elseif (in_array($word, ParseJS::$OPERATORS)) {
			return $this->token('operator', $word);
		} elseif (in_array($word, ParseJS::$KEYWORDS_ATOM)) {
			return $this->token('atom', $word);
		} else {
			return $this->token('keyword', $word);
		}
	}

	private function with_eof_error($err, $cont) {
		try {
			return $cont();
		} catch (Exception $ex) {
			if ($ex instanceof JS_EOF) {
				return $this->parse_error($err);
			}
			throw $ex;
		}
	}
	
}

class ParseJS {

	public static function tokenizer($input) {
		return new JavaScript_Tokenizer($input);
	}
	
	public static function parse($input, $exigent_mode = true, $embed_tokens = true) {
		$parser = new JavaScript_Parser($input, $exigent_mode, $embed_tokens);
		return $parser->run();
	}

// ----------------------------------------------------------------------------
//  Constants
	public static $CHANGE_FIELDS = array(
		"src","location","cookie"
	);
	public static $CHANGE_FUNCS = array(

	);
	public static $KEYWORDS = array(
		"break", "case", "catch", "const", "continue", "default", "delete",
		"do", "else", "finally", "for", "function", "if", "in", "instanceof",
		"new", "return", "switch", "throw", "try", "typeof", "var", "void",
		"while", "with"
	);

	public static $RESERVED_WORDS = array(
		"abstract", "boolean", "byte", "char", "class", "debugger", "double",
		"enum", "export", "extends", "final", "float", "goto", "implements",
		"import", "int", "interface", "long", "native", "package", "private",
		"public", "public", "short", "static", "super", "synchronized",
		"throws", "transient", "volatile"
	);

	public static $KEYWORDS_BEFORE_EXPRESSION = array(
		"return", "new", "delete", "throw", "else", "case"
	);

	public static $KEYWORDS_ATOM = array(
		"false", "null", "true", "undefined"
	);

	public static $OPERATOR_CHARS = array(
		"+", "-", "*", "&", "%", "=", "<", ">", "!", "?", "|", "~", "^"
	);

	public static $RE_HEX_NUMBER = '/^0x[0-9a-f]+$/i';
	public static $RE_OCT_NUMBER = '/^0[0-7]+$/';
	public static $RE_DEC_NUMBER = '/^\d*\.?\d*(?:e[+-]?\d*(?:\d\.?|\.?\d)\d*)?$/i';

	public static $OPERATORS = array(
		"in", "instanceof", "typeof", "new", "void", "delete", "++", "--", "+",
		"-", "!", "~", "&", "|", "^", "*", "/", "%", ">>", "<<", ">>>", "<",
		">", "<=", ">=", "==", "===", "!=", "!==", "?", "=", "+=", "-=", "/=",
		"*=", "%=", ">>=", "<<=", ">>>=", "|=", "^=", "&=", "&&", "||"
	);

	public static $WHITESPACE_CHARS = array(
		" ", "\n", "\r", "\t", "\u200b"
	);

	public static $PUNC_BEFORE_EXPRESSION = array(
		"[", "{", "}", "(", ",", ".", ";", ":"
	);

	public static $PUNC_CHARS = array(
		"[", "]", "{", "}", "(", ")", ",", ";", ":"
	);

	public static $REGEXP_MODIFIERS = array(
		"g", "m", "s", "i", "y"
	);

	// regexps adapted from http://xregexp.com/plugins/#unicode
	public static $UNICODE = array(
		'letter'                => '/[\p{L}]/u',
		'non_spacing_mark'      => '/[\p{Mn}]/u',
		'space_combining_mark'  => '/[\p{Mc}]/u',
		'connector_punctuation' => '/[\p{Pc}]/u'
	);

	public static $UNARY_PREFIX = array(
		"typeof", "void", "delete", "--", "++", "!", "~", "-", "+"
	);

	public static $UNARY_POSTFIX = array(
		"--", "++"
	);

	public static $ASSIGNMENT = null;
	public static function _ASSIGNMENT() {
		if (! self::$ASSIGNMENT) {
			$a = array("+=", "-=", "/=", "*=", "%=", ">>=", "<<=", ">>>=", "|=", "^=", "&=");
			$ret = array( '=' => true );
			foreach ($a as $i => $op) {
				$ret[$op] = substr($op, strlen($op) - 1);
			}
			self::$ASSIGNMENT = $ret;
		}
	}

	public static $PRECEDENCE = null;
	public static function _PRECEDENCE() {
		if (! self::$PRECEDENCE) {
			$a = array(
				array("||"),
				array("&&"),
				array("|"),
				array("^"),
				array("&"),
				array("==", "===", "!=", "!=="),
				array("<", ">", "<=", ">=", "in", "instanceof"),
				array(">>", "<<", ">>>"),
				array("+", "-"),
				array("*", "/", "%")
			);
			$ret = array();
			for ($i = 0, $n = 1, $c1 = count($a); $i < $c1; $i++, $n++) {
				$b = $a[$i];
				for ($j = 0, $c2 = count($b); $j < $c2; $j++) {
					$ret[$b[$j]] = $n;
				}
			}
			self::$PRECEDENCE = $ret;
		}
	}

	public static $STATEMENTS_WITH_LABELS = array(
		"for", "do", "while", "switch"
	);

	public static $ATOMIC_START_TOKEN = array(
		"atom", "num", "string", "regexp", "name"
	);

// ----------------------------------------------------------------------------
//  Utilities
	
	public static function is_letter($ch) {
		return preg_match(self::$UNICODE['letter'], $ch);
	}

	public static function is_digit($ch) {
		$ch = ord($ch);
		return ($ch >= 48 && $ch <= 57);
	}

	public static function is_alphanumeric_char($ch) {
		return (self::is_letter($ch) || self::is_digit($ch));
	}

	public static function is_unicode_combining_mark($ch) {
		return (preg_match(self::$UNICODE['space_combining_mark'], $ch) ||
			preg_match(self::$UNICODE['non_spacing_mark'], $ch));
	}

	public static function is_unicode_connector_punctuation($ch) {
		return preg_match(self::$UNICODE['connector_punctuation'], $ch);
	}
	
	public static function is_identifier_start($ch) {
		return ($ch == '$' || $ch == '_' || self::is_letter($ch));
	}

	public static function is_identifier_char($ch) {
		return (
			self::is_identifier_start($ch) ||
			self::is_unicode_combining_mark($ch) ||
			self::is_digit($ch) ||
			self::is_unicode_connector_punctuation($ch) ||
			$ch == "\u200c" || $ch == "\u200d"
		);
	}

	public static function parse_js_number($num) {
		if (preg_match(self::$RE_HEX_NUMBER, $num)) {
			return intval(substr($num, 2), 16);
		} elseif (preg_match(self::$RE_OCT_NUMBER, $num)) {
			return intval(substr($num, 1), 8);
		} elseif (preg_match(self::$RE_DEC_NUMBER, $num)) {
			return floatval($num);
		}
	}

	public static function is_token($token, $type, $value = null) {
		return ($token['t'] == $type && ($value === null || $token['v'] == $value));
	}

	public static function unichr($code) {
		return html_entity_decode('&#'.$code.';', ENT_NOQUOTES, 'UTF-8');
	}

	public static function uniord($c) {
		$h = ord($c{0});
		if ($h <= 0x7F) {
			return $h;
		} else if ($h < 0xC2) {
			return false;
		} else if ($h <= 0xDF) {
			return ($h & 0x1F) << 6 | (ord($c{1}) & 0x3F);
		} else if ($h <= 0xEF) {
			return ($h & 0x0F) << 12 | (ord($c{1}) & 0x3F) << 6
			                         | (ord($c{2}) & 0x3F);
		} else if ($h <= 0xF4) {
			return ($h & 0x0F) << 18 | (ord($c{1}) & 0x3F) << 12
			                         | (ord($c{2}) & 0x3F) << 6
			                         | (ord($c{3}) & 0x3F);
		} else {
			return false;
		}
	}

}

class JS_Token {
	public $type  = null;
	public $value = null;
	//public $line  = null;
	//public $col   = null;
	public $pos   = null;
	//public $nlb   = null;
	//public $comments_before = null;
	public function __construct($type, $value, $line, $col, $pos, $nlb) {
		$this->type  = $type;
		$this->value = $value;
		//$this->line  = $line;
		//$this->col   = $col;
		$this->pos   = $pos;
		//$this->nlb   = $nlb;
	}
}

class JS_EOF extends Exception { }

class JS_Parse_Error extends Exception {
	
	public $js_message  = null;
	public $js_line     = null;
	public $js_col      = null;
	public $js_pos      = null;

	public function __construct($msg, $line, $col, $pos) {
		$this->js_message  = $msg;
		$this->js_line     = $line;
		$this->js_col      = $col;
		$this->js_pos      = $pos;
		parent::__construct($this->as_string(false), E_USER_WARNING);
	}

	public function __toString() {
		return $this->as_string(true);
	}

	public function as_string($with_stack = false) {
		$ret = $this->js_message.' (line: '.$this->js_line.', col: '.$this->js_col.', pos: '.$this->js_pos.')';
		if ($with_stack) {
			$ret .= "\n\n".$this->getTraceAsString();
		}
		return $ret;
	}	
}