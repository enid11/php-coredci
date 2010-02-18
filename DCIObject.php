<?php
	/**
	* This class provides base functionality for the rest of the framework.
	* The DCIObject class is meant to be extended and as a result provides
	* the ability to implement role methods, properties, and performs all 
	* necessary redirection to fully implement DCI
	*
	* @author Joe Chrzanowski <joechrz@gmail.com>
	* @version 3.0
	*/
	class DCIObject {
		/**
        * array to hold the parent role for all injected methods
        */
		var $methods = array();
		
		/**
		* Reverse index of methods -- stores a list of roles
		* and their respective methods.
		*/
		var $roles = array();
		
		/**
		* the $fallback variable solves problems with conflicting methods and
		* properties by defining a default role to use in the case of ambiguity.
		*/
		var $fallback = '';

		/**
		* The DCIObject constructor
		*
		* This function must be called by an inheriting class (parent::__construct())
		* in order for the DCI functionality to work properly.  More specifically
		* this function adds all implemented role methods and properties to the index
		* maintained in this class.
		*/
        function __construct() {
        	foreach (class_implements($this) as $role) {
        	    $roleclass = $role . "Actions";
        	    if (class_exists($roleclass))
        		    $this->addRole($role);
        	}
        }
		
		/**
		* While it is technically possible to add a role after initialization,
		* the type hinting used in role and context methods can only happen at
		* compile-time.
		*/
		private function addRole($role) {
		    $roleclass = $role . "Actions";
			if (interface_exists($role) && class_exists($roleclass)) {
			    $this->roles[$role] = array();
			    $this->roles[$role]['methods'] = array();
    			foreach (get_class_methods($roleclass) as $method) {
    			    $this->roles[$role]['methods'][$method] = true;
    			    if (array_key_exists($method,$this->methods))
    			        $this->methods[$method][] = $role;
    			    else
    			        $this->methods[$method] = array($role);
    			}
    			$this->fallback = $role;
    		}
    		else throw new RoleNotFoundException($role);
		}
		
		/**
		* Explicitly specify both the role and method name.  Essentially equivalent to:
		* rRoleNameActions::method($this, ... )
		*
		* Calling an injected method eventually gets routed to this function.
		*/
		function call_explicit($role, $method, $args) {
		    array_unshift($args, $this);
		    $roleclass = $role . "Actions";
		    if (class_exists($roleclass) && method_exists($roleclass,$method))
                return call_user_func_array(array($roleclass,$method),$args);
            else 
                throw new MethodNotFoundException("$role::$method",$this->roles[$role]);
		}
		
		/**
		* Call a role method:
		*   First, check for ambiguity.  If unambiguous, call method
		*   If ambiguous, see if the fallback role contains the method
		*   If not, call the most recently declared method and throw a notice
		*   If all else fails, MethodNotFoundException
		*/
        function __call($func, $args) {
            if (isset($this->methods[$func]) && count($this->methods[$func]) > 1) {
                foreach ($this->methods[$func] as $role) {
                    if ($role == $this->fallback) 
                        return $this->call_explicit($this->fallback, $func, $args);
                }
                
    		    trigger_error("<b>Ambiguous Method</b>: $func in $trace[file]:$trace[line]" . 
    		        "<br /><pre>" . print_r($this->methods[$func],true) . "</pre>");
    		        
    		    return $this->call_explicit(end($this->methods[$func]), $func, $args);
		    }
		    else if (isset($this->methods[$func]) && is_array($this->methods[$func]))
		        return $this->call_explicit(end($this->methods[$func]), $func, $args);
		    else 
		        throw new MethodNotFoundException("::$func",array_keys($this->roles));
        }
		
	}
?>