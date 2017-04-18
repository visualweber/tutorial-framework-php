<?php
	/**
	* Router
	*/
	class Router
	{
		private $routers = [];

		function __construct()
		{
			# code...
		}

		private function getRequestURL(){
			$url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
			$url = str_replace('/www/my-framework/public', '', $url);
			$url = $url === '' || empty($url) ? '/' : $url;
			return $url;
		}

		private function getRequestMethod(){
			$method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
			return $method;
		}

		private function addRouter($method,$url,$action){
			$this->routers[] = [$method,$url,$action];
		}

		public function get($url,$action){
			$this->addRouter('GET',$url,$action);
		}

		public function post($url,$action){
			$this->addRouter('POST',$url,$action);
		}

		public function any($url,$action){
			$this->addRouter('GET|POST',$url,$action);
		}

		public function map(){

			$checkRoute = false;
			$params 	= [];

			$requestURL = $this->getRequestURL();
			$requestMethod = $this->getRequestMethod();
			$routers = $this->routers;
			
			foreach( $routers as $route ){
				list($method,$url,$action) = $route;

				if( strpos($method, $requestMethod) === FALSE ){
					continue;
				}

				if( $url === '*' ){
					$checkRoute = true;
				}elseif( strpos($url, '{') === FALSE ){
					if( strcmp(strtolower($url), strtolower($requestURL)) === 0 ){
						$checkRoute = true;
					}else{
						continue;
					}
				}elseif( strpos($url, '}') === FALSE ){
					continue;
				}else{
					$routeParams 	= explode('/', $url);
					$requestParams 	= explode('/', $requestURL);

					if( count($routeParams) !== count($requestParams) ){
						continue;
					}

					foreach( $routeParams as $k => $rp ){
						if( preg_match('/^{\w+}$/',$rp) ){
							$params[] = $requestParams[$k];
						}
					}
					
					$checkRoute = true;
				}

				if( $checkRoute === true ){
					if( is_callable($action) ){
						call_user_func_array($action, $params);
					}
					elseif( is_string($action) ){
						$this->compieRoute($action,$params);
					}
					return;
				}else{
					continue;
				}
			}
			return;
		}

		private function compieRoute($action, $params){

			if( count(explode('@', $action)) !== 2 ){
				die('Router error');
			}

			$className = explode('@', $action)[0];
			$methodName = explode('@', $action)[1];

			$classNamespace = 'app\\controllers\\'.$className;

			if( class_exists($classNamespace) ){
				$object = new $classNamespace;

				if( method_exists($classNamespace, $methodName) ){
					call_user_func_array([$object,$methodName], $params);
				}else{
					die('Method "'.$methodName.'" not found');
				}
			}else{
				die('Class "'.$classNamespace.'" not found');
			}
		}

		function run(){
			$this->map();
		}
	}
?>