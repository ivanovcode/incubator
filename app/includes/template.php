<?php
class websun {

    public $vars;
    public $templates_root_dir;
    public $templates_current_dir;
    public $TIMES;
    public $no_global_vars;
    public $current_template_filepath;
    private $profiling;
    private $predecessor;

    private $default_allowed_callbacks = array(
        'array_key_exists',
        'date',
        'htmlspecialchars',
        'implode',
        'in_array',
        'is_array',
        'is_null',
        'json_encode',
        'mb_lcfirst',
        'mb_ucfirst',
        'rand',
        'round',
        'strftime',
        'urldecode',
        'var_dump',
    );

    function __construct($options) {
        $this->vars = $options['data'];

        if (isset($options['templates_root']) AND $options['templates_root']) // корневой каталог шаблонов
            $this->templates_root_dir = $this->template_real_path( rtrim($options['templates_root'], '/') );
        else {
            foreach (debug_backtrace() as $trace) {
                if (preg_match('/^websun_parse_template/', $trace['function'])) {
                    $this->templates_root_dir = dirname($trace['file']);
                    break;
                }
            }

            if (!$this->templates_root_dir) {
                foreach (debug_backtrace() as $trace) {
                    if ($trace['class'] == 'websun') {
                        $this->templates_root_dir = dirname($trace['file']);
                        break;
                    }
                }
            }
        }
        $this->templates_current_dir = $this->templates_root_dir . '/';

        $this->predecessor = (isset($options['predecessor']) ? $options['predecessor'] : FALSE);

        $this->allowed_extensions = (isset($options['allowed_extensions']))
            ? $options['allowed_extensions']
            : array( 'tpl', 'html', 'css', 'js', 'xml' );

        $this->no_global_vars = (isset($options['no_global_vars']) ? $options['no_global_vars'] : FALSE);

        $this->profiling = (isset($options['profiling']) ? $options['profiling'] : FALSE);
    }

    /**
     * Парсит шаблон
     *
     * @param $template
     * @return mixed
     */
    function parse_template($template) {
        if ($this->profiling)
            $start = microtime(1);

        $template = preg_replace('/ \\/\* (.*?) \*\\/ /sx', '', $template); /**ПЕРЕПИСАТЬ ПО JEFFREY FRIEDL'У !!!**/

        $template = str_replace('\\\\', "\x01", $template);
        $template = str_replace('\*', "\x02", $template);

        $template = $this->find_and_parse_cycle($template);

        $template = $this->find_and_parse_if($template);

        $template = preg_replace_callback( // переменные, шаблоны и функции
            '/
				{\*
				(
					(?:
						[^*]*+
						|
						\*(?!})
					)+
				)	
				\*}
				/x',
            array($this, 'parse_vars_templates_functions'),
            $template
        );

        $template = str_replace("\x01", '\\\\', $template);
        $template = str_replace("\x02", '*', $template);

        if ($this->profiling AND !$this->predecessor) {
            $this->TIMES['_TOTAL'] = round(microtime(1) - $start, 4) . " s";
            echo '<pre>' . print_r($this->TIMES, 1) . '</pre>';
        }

        return $template;
    }

    function var_value($string) {

        if ($this->profiling)
            $start = microtime(1);

        if (mb_strpos($string, '|') !== FALSE) {
            $f = __FUNCTION__;

            foreach (explode('|', $string) as $str) {
                if ( $val = $this->$f(trim($str)) )
                    break;
            }

            $out = $val;
        }
        elseif (mb_substr($string, 0, 1) == '=') {
            $C = mb_substr($string, 1);
            $out = (defined($C)) ? constant($C) : '';
        }

        elseif (
            mb_substr($string, 0, 1) == '"'
            AND
            mb_substr($string, -1) == '"'
        )
            $out = mb_substr($string, 1, -1);

        elseif (is_numeric($string))
            $out = $string + 0;

        elseif ($string == 'FALSE' OR $string == 'false')
            $out = FALSE;

        elseif ($string == 'TRUE' OR $string == 'TRUE')
            $out = TRUE;

        else {

            if (mb_substr($string, 0, 1) == '$') {
                if (!$this->no_global_vars) {
                    $string = mb_substr($string, 1);
                    $value = $GLOBALS;
                }
                else
                    $value = '';
            }
            else
                $value = $this->vars;

            if (mb_substr($string, -6) == '^COUNT') {
                $string = mb_substr($string, 0, -6);
                $return_mode = 'count';
            }
            else
                $return_mode = FALSE; // default

            $rawkeys = explode('.', $string);
            $keys = array();
            foreach ($rawkeys as $v) {
                if ($v !== '')
                    $keys[] = $v;
            }

            foreach($keys as $k) {
                if (is_array($value) AND isset($value[$k]))
                    $value = $value[$k];

                elseif (is_object($value) AND property_exists($value, $k))
                    $value = $value->$k;

                else {
                    $value = NULL;
                    break;
                }
            }

            $out = (!$return_mode)
                ? $value

                : ( is_array($value) ? count($value) : FALSE )

            ;
        }

        if ($this->profiling)
            $this->write_time(__FUNCTION__, $start, microtime(1));

        return $out;
    }

    function find_and_parse_cycle($template) {
        if ($this->profiling)
            $start = microtime(1);
        $out = preg_replace_callback(
            '/
			{ %\* ([^*]*) \* }
			( (?: [^{]* | (?R) | . )*? )
			{ (?: % | \*\1\*% ) }
			/sx',
            array($this, 'parse_cycle'),
            $template
        );

        if ($this->profiling)
            $this->write_time(__FUNCTION__, $start, microtime(1));

        return $out;
    }

    function parse_cycle($matches) {

        if ($this->profiling)
            $start = microtime(1);

        $array_name = $matches[1];
        $array = $this->var_value($array_name);

        if ( ! is_array($array) )
            return FALSE;

        $parsed = '';

        $dot = ( $array_name != '' AND $array_name != '$' )
            ? '.'
            : '';

        $array_name_quoted = preg_quote($array_name);

        $array_name_quoted = str_replace('/', '\/', $array_name_quoted); //

        $i = 0; $n = 1;
        foreach ($array as $key => $value) {
            $parsed .= preg_replace(
                array(
                    "/(?<=[*=<>|&%])\s*$array_name_quoted\:\^VALUE\b/",
                    "/(?<=[*=<>|&%])\s*$array_name_quoted\:\^KEY\b/",
                    "/(?<=[*=<>|&%])\s*$array_name_quoted\:\^i\b/",
                    "/(?<=[*=<>|&%])\s*$array_name_quoted\:\^N\b/",
                    "/(?<=[*=<>|&%])\s*$array_name_quoted\:/"
                ),
                array(
                    '"' . $value . '"',
                    '"' . $key . '"',
                    '"' . $i . '"',
                    '"' . $n . '"',
                    $array_name . $dot . $key . '.'
                ),
                $matches[2]
            );
            $i++; $n++;
        }
        $parsed = $this->find_and_parse_cycle($parsed);

        if ($this->profiling)
            $this->write_time(__FUNCTION__, $start, microtime(1));

        return $parsed;
    }

    function find_and_parse_if($template) {

        if ($this->profiling)
            $start = microtime(1);

        $out = preg_replace_callback(
            '/
				{ (\?\!?) \*  # открывающая "скобка"
				
				  (           # условие для проверки 
					(?:
						[^*]*+       # строгое выражение, никогда не возвращающееся назад;
						|            # буквально означает "любые символы, кроме звёздочки,
						\* (?! } )   # либо звёздочка, если только за ней сразу не следует
					)+               # закрывающая фигурная скобка
				   )     
				\*}      
				
				( (?: [^{]* | (?R) | . )*? ) # при положительном проверки результате (+ рекурсия)
				(?:
				  { \?\! } 
				  ( (?: [^{]* | (?R) | . )*? ) # при отрицательном результате проверки
				)? #  
				{ (?: \?  | \*\2\*\1 ) }     # закрывающая скобка
				/sx',
            array($this, 'parse_if'),
            $template
        );

        if ($this->profiling)
            $this->write_time(__FUNCTION__, $start, microtime(1));

        return $out;
    }

    function parse_if($matches) {

        if ($this->profiling)
            $start = microtime(1);

        $final_check = FALSE;

        $separator = (strpos($matches[2], '&'))
            ? '&'
            : '|';
        $parts = explode($separator, $matches[2]);
        $parts = array_map('trim', $parts);

        $checks = array();

        foreach ($parts as $p)
            $checks[] = $this->check_if_condition_part($p);

        if ($separator == '|')
            $final_check = in_array(TRUE, $checks);

        else
            $final_check = !in_array(FALSE, $checks);

        $result = ($matches[1] == '?')
            ? $final_check
            : !$final_check ;

        $parsed_if = ($result)
            ? $this->find_and_parse_if($matches[3])
            : ( (isset($matches[4])) ? $this->find_and_parse_if($matches[4]) : '' ) ;

        if ($this->profiling)
            $this->write_time(__FUNCTION__, $start, microtime(1));

        return $parsed_if;
    }


    function check_if_condition_part($str) {

        if ($this->profiling)
            $start = microtime(1);

        preg_match(
            '/^
				   (  
				   	"[^"*]*"     # строковый литерал
				   	
				   	|            # или
				   	
				   	=?[^!<>=]*+   # имя константы или переменной или вызов функции
				   )  
				   
					(?: # если есть сравнение с чем-то:
						\s*
						(!?==?|<|>)  # знак сравнения 
						\s*
						(.*)     # то, с чем сравнивают
					)?
					
					$
				/x',
            $str,
            $matches
        );

        $left = ( strpos(trim($matches[1]), '@') === 0 )
            ? $this->parse_vars_templates_functions( array( 1 => $matches[1] ) )
            : $this->var_value(trim($matches[1])) ;

        if ( is_null($left) )
            $check = FALSE;

        else {

            if (!isset($matches[2]))
                $check = ($left == TRUE);

            else {

                if (isset($matches[3]))
                    $right = ( strpos(trim($matches[3]), '@') === 0 )
                        ? $this->parse_vars_templates_functions( array( 1 => $matches[3] ) ) # вызов функции
                        : $this->var_value(trim($matches[3]));
                else
                    $right = FALSE ;

                if ( is_null($right) )
                    $check = FALSE;
                else
                    switch($matches[2]) {
                        case '=': $check = ($left == $right); break;
                        case '!=': $check = ($left != $right); break;
                        case '==': $check = ($left === $right); break;
                        case '!==': $check = ($left !== $right); break;
                        case '>': $check = ($left > $right); break;
                        case '<': $check = ($left < $right); break;
                        default: $check = ($left == TRUE);
                    }
            }
        }

        if ($this->profiling)
            $this->write_time(__FUNCTION__, $start, microtime(1));

        return $check;
    }


    function parse_vars_templates_functions($matches) {
        if ($this->profiling)
            $start = microtime(1);

        $work = $matches[1];

        $work = trim($work);

        if (mb_substr($work, 0, 1) == '@') {

            $p = '/
				^
				( # 1 - вызов функции 
					@ 
					( [^(]++ ) # 2 - имя функции
					\( 
					( # 3 - аргументы 
						(?: 
							[^@()"]++  
							| 
							"[^"]*+"
							|
							(?1)
							# \s*+ ( (?1) (?:\s*+,\s*+)? )+ \s*+
								# рекурсивным на весь шаблон (?R) это выражение делать нельзя,
								# т.к. здесь есть еще часть, отвечающая за подключение шаблона
						)* 
					) 
					\) 
				)
				(?: 
					\s*+ 
					\| 
					\s*+ (.++) # 4 - вызов шаблона 
				)? 
				$
				/x';

            if (preg_match( $p, $work, $m) ) {

                $function_string = trim($m[2]);


                preg_match('/^\*([^*]++)\*(?:->(\w+))?$/', $function_string, $w);

                $call;

                if (!$w) {

                    $tmp = explode('::', $function_string);

                    if (count($tmp) == 1)
                        $call = array(
                            'function' => $function_string,
                            'for_check' => $function_string
                        );

                    else
                        $call = array(
                            'class' => $tmp[0],
                            'method' => $tmp[1],
                            'for_check' => "$tmp[0]::$tmp[1]"
                        );
                }
                else {
                    $var = $this->var_value($w[1]);

                    if (!isset($w[2]))
                        $call = array( 'function' => $var, 'for_check' => $var );

                    else
                        $call = array(
                            'object' => $var,
                            'method' => $w[2],
                            'for_check' => get_class($var) . "::$w[2]"
                        );

                    unset($var);

                }
                unset($w);

                global $WEBSUN_ALLOWED_CALLBACKS;
                $list = array_unique( array_merge(
                    $this->default_allowed_callbacks,
                    isset($WEBSUN_ALLOWED_CALLBACKS) ? $WEBSUN_ALLOWED_CALLBACKS : []
                ) );


                if ($list and in_array($call['for_check'], $list) )
                    $allowed = TRUE;
                else {
                    $allowed = FALSE;
                    trigger_error("'$call[for_check]()' is not in the list of allowed callbacks.", E_USER_WARNING);
                }

                if ($allowed) {

                    $args = array();

                    if (isset($m[3])) {

                        preg_match_all('
							/ 
								# выражение составлено так, что в каждой подмаске
								# должен совпасть хотя бы один символ 
								# v. 0.2.03: сначала ловим строки вида *"..."*, которые остаются от подстановки *:^KEY* 
								\*"[^"]*+"\* 
								|
								( @ [^(]++ \( \s*+ (?: (?R) \s*+,?\s*+ )? \) ) # вложенные вызовы функций (v. 0.3.0) 
									# Этот подшаблон обязательно должен идти перед следующим, т.к. иначе там будет захвачено имя функции
									# пробелы и запятые указаны в явном виде, т.к. нигде больше в шаблоне они не встречаются и он с ними не совпадает
									# если их так не указать, участок рекурсивного совпадения
									# будет неправильно фрагментирован.
									# Случай, когда содержимое скобок пусто (не переданы аргументы),
									# также нужно описывать в явном виде, поскольку строка
									# , начинающаяся с пробела, с данным шаблоном не совпадает
								|
								[^ \s,"{\[@() ]++ # переменные, константы или числа (ведущий пробел тоже исключаем) 
								|
								"[^"]*+" # строки
								|
								( \[ (?: [^\[\]]*+ | (?2) )* \] ) # JSON: обычные массивы (с числовыми ключами)
								|
								( { (?: [^{}]*+ | (?3) )* } ) # JSON: ассоциативные массивы
								
							/x',
                            $m[3],
                            $tmp
                        );

                        if ($tmp)
                            $args = array_map( array($this, 'get_var_or_string'), $tmp[0] );

                        unset($tmp);
                    }

                    if (isset($call['function']))
                        $callback = $call['function'];

                    else {

                        if (isset($call['class']))
                            $callback[] = $call['class'];
                        else
                            $callback[] = $call['object'];

                        $callback[] = $call['method'];
                    }

                    $subvars = call_user_func_array($callback, $args);

                    if ( isset($m[4]) )
                        $html = $this->call_template($m[4], $subvars);

                    else
                        $html = $subvars;
                }
                else
                    $html = '';
            }
            else
                $html = '';
        }
        elseif (mb_substr($work, 0, 1) == '+') {

            $html = '';
            $parts = preg_split(
                '/(?<=[\*\s])\|(?=[\*\s])/',
                mb_substr($work, 1)

            );
            $parts = array_map('trim', $parts);
            if ( !isset($parts[1]) ) {

                $html = $this->call_template($parts[0], $this->vars);
            }
            else {
                $varname_string = mb_substr($parts[0], 1, -1);

                $indicator = mb_substr($varname_string, 0, 1);
                if ($indicator == '?') {
                    if ( $subvars = $this->var_value( mb_substr($varname_string, 1) ) )

                        $html = $this->call_template($parts[1], $subvars);
                }
                elseif ($indicator == '%') {
                    if ( $subvars = $this->var_value( mb_substr($varname_string, 1) ) ) {
                        foreach ( $subvars as $row ) {

                            $html .= $this->call_template($parts[1], $row);
                        }
                    }
                }
                else {
                    $subvars = $this->var_value( $varname_string );

                    $html = $this->call_template($parts[1], $subvars);
                }
            }
        }
        else
            $html = $this->var_value($work);

        if ($this->profiling)
            $this->write_time(__FUNCTION__, $start, microtime(1));

        return $html;
    }

    function parse_function($str) {



    }

    function call_template($template_notation, $vars) {
        if ($this->profiling)
            $start = microtime(1);

        $c = __CLASS__;

        $subobject = new $c(array(
            'data' => $vars,
            'templates_root' => $this->templates_root_dir,
            'predecessor' => $this,
            'no_global_vars' => $this->no_global_vars,
            'allowed_extensions' => $this->allowed_extensions,
        ));

        $template_notation = trim($template_notation);

        if (mb_substr($template_notation, 0, 1) == '>') {
            $v = mb_substr($template_notation, 1);
            $subtemplate = $this->get_var_or_string($v);
            $subobject->templates_current_dir = $this->templates_current_dir;
        }
        else {
            $path = ($template_notation === '^T')
                ? $this->current_template_filepath
                : $this->get_var_or_string($template_notation);
            $subobject->templates_current_dir = pathinfo($this->template_real_path($path), PATHINFO_DIRNAME ) . '/';
            $subobject->current_template_filepath = $path;
            $subtemplate = $this->get_template($path);
        }

        $result = $subobject->parse_template($subtemplate);

        if ($this->profiling)
            $this->write_time(__FUNCTION__, $start, microtime(1));

        return $result;
    }

    function get_var_or_string($str) {

        $str = trim($str);

        if ($this->profiling)
            $start = microtime(1);

        $first_char = mb_substr($str, 0, 1);

        if ($first_char == '*')
            $out = $this->var_value( mb_substr($str, 1, -1) );

        elseif ($first_char == '[' OR $first_char == '{') {
            $out = json_decode($str, TRUE);
            $json_decode_status = json_last_error();
            if ($json_decode_status !== JSON_ERROR_NONE)
                trigger_error("Error (code = $json_decode_status) parsing JSON array literal $str", E_USER_WARNING);
        }

        elseif ($first_char == '@')
            $out = $this->parse_vars_templates_functions( array( 1 => $str ) );


        else
            $out = ($first_char == '"')
                ? mb_substr($str, 1, -1)
                : $str ;

        if ($this->profiling)
            $this->write_time(__FUNCTION__, $start, microtime(1));

        return $out;
    }

    function get_template($tpl) {
        if ($this->profiling)
            $start = microtime(1);

        if (!$tpl) return FALSE;

        $tpl_real_path = $this->template_real_path($tpl);

        $ext = pathinfo($tpl_real_path, PATHINFO_EXTENSION);

        if (!in_array($ext, $this->allowed_extensions)) {
            trigger_error(
                "Template's <b>$tpl_real_path</b> extension is not in the allowed list ("
                . implode(", ", $this->allowed_extensions) . "). 
				 Check <b>allowed_extensions</b> option.",
                E_USER_WARNING
            );
            return '';
        }

        $out = preg_replace(
            '/\r?\n$/',
            '',
            file_get_contents($tpl_real_path)
        );

        if ($this->profiling)
            $this->write_time(__FUNCTION__, $start, microtime(1));

        return $out;
    }

    function template_real_path($tpl) {
        if ($this->profiling)
            $start = microtime(1);

        $dir_indicator = mb_substr($tpl, 0, 1);

        $adjust_tpl_path = TRUE;

        if ($dir_indicator == '^') $dir = $this->templates_root_dir;
        elseif ($dir_indicator == '$') $dir = $_SERVER['DOCUMENT_ROOT'];
        elseif ($dir_indicator == '/') { $dir = ''; $adjust_tpl_path = FALSE; }
        else {
            if (mb_substr($tpl, 1, 1) == ':')
                $dir = '';
            else
                $dir = $this->templates_current_dir;

            $adjust_tpl_path = FALSE;
        }

        if ($adjust_tpl_path) $tpl = mb_substr($tpl, 1);

        $tpl_real_path = $dir . $tpl;

        if ($this->profiling)
            $this->write_time(__FUNCTION__, $start, microtime(1));

        return $tpl_real_path;
    }

    function write_time($method, $start, $end) {

        if (!$this->predecessor)
            $time = &$this->TIMES;

        else
            $time = &$this->predecessor->TIMES ;

        if (!isset($time[$method]))
            $time[$method] = array(
                'n' => 0,
                'last' => 0,
                'total' => 0,
                'avg' => 0
            );

        $time[$method]['n'] += 1;
        $time[$method]['last'] = round($end - $start, 4);
        $time[$method]['total'] += $time[$method]['last'];
        $time[$method]['avg'] = round($time[$method]['total'] / $time[$method]['n'], 4) ;
    }
}


function websun_parse_template_path(
    $data,
    $template_path,
    $templates_root_dir = FALSE,
    $no_global_vars = FALSE
) {
    $W = new websun(array(
        'data' => $data,
        'templates_root' => $templates_root_dir,
        'no_global_vars' => $no_global_vars,
    ));
    $tpl = $W->get_template($template_path);
    $W->current_template_filepath = $template_path;
    $W->templates_current_dir = pathinfo( $W->template_real_path($template_path), PATHINFO_DIRNAME ) . '/';
    $string = $W->parse_template($tpl);
    return $string;
}

function websun_parse_template(
    $data,
    $template_code,
    $templates_root_dir = FALSE,
    $no_global_vars = FALSE
) {
    $W = new websun(array(
        'data' => $data,
        'templates_root' => $templates_root_dir,
        'no_global_vars' => $no_global_vars
    ));
    $string = $W->parse_template($template_code);
    return $string;
}