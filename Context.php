<?php
    /**
    * The Context class provides a means to execute role methods
    * with explicit typecasting.  By specifying type-hinted roles
    * in the definition of the child class' Execute function, the 
    * parent Context::Execute function can explicitly tell any 
    * DCIObjects which roles they should be playing in a given 
    * context.
    *
    * @author Joe Chrzanowski
    * @version 1.0
    * @package CoreDCI
    */
    abstract class Context {
        /**
        * This method should be called from an inheriting object's
        * Execute() function (thus the protected scope).  This method 
        * causes the DCIObjects passed to the child's Execute() method 
        * to be auto-type-casted to the role specified in the declaration.
        *
        * @return mixed
        */
        protected function Execute() {
            // reflect the function to determine the casted types
    	    $var = new ReflectionMethod($this,'Execute');
    	    $params = $var->getParameters();
    	    $types = array();
    	    
            $trace = debug_backtrace();
            $call = array_shift($trace);
            $call = array_shift($trace);
            $args = $call['args'];

    	    foreach ($params as $pindex => $param) {
    	        $str = $param->__toString();
    	        $matches = array();

    	        // find out which role we're casting too for each var
    	        // Note!: the regex looks for the 'r' prefix before a role name
    	        preg_match_all("/\s+(r[a-z0-9_]+)\s+\\\$([a-z_][a-z0-9_]+)/i",$str,$matches);
                
    	        // foreach match (realistically 1 or 0 matches will occur)
                if (isset($matches[1][0])) {
        	        $role = $matches[1][0];
        	        $theobj = $args[$pindex];                       // create a reference to it
                    $theobj->fallback = $role;                 // update to the role we're casting to
                }
    	    }
        }
    }
?>