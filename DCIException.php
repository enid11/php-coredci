<?php
    /** 
    * The base exception class for DCI exceptions.  Provides
    * significant enhancements over the base Exception class,
    * including dci-intelligent stack tracing, code referencing, and
    * detailed exception information.
    *
    * @author Joe Chrzanowski
    * @version 1.0
    * @package CoreDCI
    */
    class DCIException extends Exception {
        protected $title = "";
		protected $details = "";
		protected $code = 0;
		protected $file = '';
		protected $line = 0;
		
		/**
		* Defines the structure of a DCI call:
		*
		*   1 Object calls a Role method
		*  *2 DCIObject receives the call via __call
		*  *3 DCIObject reroutes to the call_explicit method
		*  *4 DCIObject calls the static role method with $this prepended
		*   5 rRole::method(args)
		*/
		private $call_pattern = array(
	        array('DCIObject','__call'),
	        array('DCIObject','call_explicit'),
	        array('','call_user_func_array')
	    );
		
        /**
		* Default exception constructor.  Receives the title and a textual
		* representation of the details to display
		*
		* @param string $title The short description of the exception
		* @param mixed $details The long description or an array to display
		* @param int $code The error code for this exception
		* @param string $file The file in which the exception was thrown
		* @param int $line The line at which the exception was thrown
		*/
		function __construct($title, $details = "", $code = NULL, $file = NULL, $line = NULL) {
			$this->title = $title;
			$this->details = (is_array($details) ? "<pre>" . print_r($details,true) . "</pre>" : $details);
			$this->code = $code;

			$this->file = (is_null($file) ? $this->getFile() : $file);
			$this->line = (is_null($line) ? $this->getLine() : $line);
		}
		
		/**
		* Translates an argument into a string
		*
		* @param mixed $arg The argument to convert to a string
		* @return string
		*/
		private function argToString($arg) {
		    if (is_object($arg))
                return get_class($arg);
            else if (is_array($arg)) {
                $str = ' [';
                $args = array();
                foreach ($arg as $thearg) {
                    $args[] = $this->argToString($thearg);
                }
                $str .= implode(", ",$args);
                $str .= '] ';
                return $str;
            }
            else if (is_string($arg))
                return "'" . htmlentities($arg) . "'";
            else if (is_null($arg))
                return "NULL";
            else if (empty($arg))
                return "''";
            else return $arg;
		}
		
		/**
		* Analyzes a backtrace and rebuilds the original call
		* 
		* @param array $call The trace of the call as returned by $this->getTrace()
		* @return string
		*/
		function rebuildCall($call) {
		    $rebuilt = "";
		    if (isset($call['class'])) {
		        // object method call
		        $rebuilt .= $call['class'];
		        $rebuilt .= $call['type'];
		    }
		    
	        $rebuilt .= $call['function'];
	        $args = array();
	        foreach ($call['args'] as $arg) {
	            $args[] = $this->argToString($arg);
	        }
	        $rebuilt .= "(" . implode(", ",$args) . ")";
	        return $rebuilt;
		}
		
		/**
		* Looks for DCI calls in a trace
		*/
		function detectCalls($trace) {
		    $newtrace = array();
		    $tmptrace = array();
		    
		    for ($x = 0; $x < count($trace); $x++) {
		        $tmptrace = array();
		        
		        for ($y = 0; $y < count($this->call_pattern); $y++) {
		            list($obj,$func) = $this->call_pattern[$y];
		            
		            // check for a match
		            if (!empty($obj) && (!isset($trace[$x+$y]['class']) || $trace[$x+$y]['class'] != $obj))
		                break;
		            if (!empty($func) && $trace[$x+$y]['function'] != $func)
		                break;
		            
		            $tmptrace[] = $trace[$x+$y];
		        }
		        
		        if (count($tmptrace) == count($this->call_pattern)) {
		            $tmptrace[] = $trace[$x+$y];
		            $newtrace[] = $tmptrace;
		            $x += count($tmptrace);
		        }
		        else 
		            $newtrace[] = $trace[$x];
		    }
		    return $newtrace;
		}
		
		/**
		* Returns the title for the exception
		*/
		function getTitle() { return $this->title; }
		/**
		* Returns the details of the exception
		*/
		function getDetails() { return $this->details; }
		
		/**
		* Returns a detailed stack trace that more accurately portrays the
		* call structure.
		*/
		function getDCITrace() {
		    $trace = $this->getTrace();
		    $trace = array_reverse($trace);
		    
		    $dcitrace = $this->detectCalls($trace);
		    
		    foreach ($dcitrace as $index => $call) {
		        if (isset($call[0])) {
		            foreach ($call as $sindex => $subcall) {
		                $call[$sindex]['call'] = $this->rebuildCall($subcall);
		            }
		        }
		        else {
		            $dcitrace[$index]['call'] = $this->rebuildCall($call);
		        }
		    }
		    
		    return $dcitrace;
		}
		
		/**
		* Gets a traceline string
		*/
		function getTraceLine($dcicall, $subcall = false) {
		    $msgbuf = "<tr>";
            if (!isset($dcicall['file'])) {
                $dcicall['file'] = '';
                $dcicall['line'] = '<em>implicit</em>';
            }
            
            $file = explode("/",$dcicall['file']);
            
            $msgbuf .= "<td>";
            
            $text = array_pop($file) . ":" . $dcicall['line'];
            if ($subcall)
                $text = "<span style='margin-left:25px;'>$text</span>";
            
            $msgbuf .= "<code>$text</code></td>";
            
            $text = $this->rebuildCall($dcicall);
            if ($subcall)
                $text = "<span style='margin-left:25px;'>$text</span>";
                
            $msgbuf .= "<td style='border-left:solid 1px gray;'><code>$text</code></td></tr>";
            return $msgbuf;
		}
		
		/**
		* Returns a detailed, textual representation of the exception
		* @return string
		*/
		function __toString() {
			$msgbuf = "<hr /><strong>Uncaught " . get_class($this) . "</strong>" . "<br />";
			$msgbuf .= "<dl><dt>" . $this->title . "</dt>";
			$msgbuf .= "<dd>" . $this->details . "</dd>";
			$msgbuf .= "<br />";

            $trace = $this->getDCITrace();
			
			$msgbuf .= "<strong>Trace / Call Stack</strong>";
			$msgbuf .= "<table cellpadding='3' style='width:100%;'>";
			$tracelines = array();
			foreach ($trace as $indx => $tr) {
			    if (isset($tr[0])) {
			        foreach ($tr as $dcicall) {
			            $tracelines[] = $this->getTraceLine($dcicall,true);
			        }
			    }
			    else {
			        $tracelines[] = $this->getTraceLine($tr);
			    }
			}
			$msgbuf .= implode("",$tracelines);
		    $msgbuf .= "</table><hr />";
		    return $msgbuf; 
		}
    }
    
    // classes of exceptions for DCIObjects and Context objects
    class RoleMethodNotFoundException extends DCIException {}
    
    /**
    * register a new exception handler to accomodate
    * the size of the output generated by the DCIException's
    * __toString method.
    *
    * @param Exception $exception The exception to handle
    */
    function dci_exception_handler($exception) {
        @ob_end_flush();
        echo $exception->__toString();
    }
?>