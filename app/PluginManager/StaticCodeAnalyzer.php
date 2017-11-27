<?php

namespace Capetown\Runner\PluginManager;

use Capetown\Core\CommandInterface;

class StaticCodeAnalyzer {
	public function getFQN(string $phpFilePath): string {
		$contents = file_get_contents($phpFilePath);
		
		$namespace  = '';
		$className  = '';
		
		//Go through each token and evaluate it as necessary
		$tokens = token_get_all($contents);
		for ($i = 0; $i < count($tokens); $i++) {
			if (is_array($tokens[$i]) && $tokens[$i][0] === T_NAMESPACE) {
				//Current token is `namespace` the actual namespace starts at the next one
				while ($tokens[++$i] !== ';') {
					//Add all the namespace tokens until we hit a ;
					if (is_array($tokens[$i]) && in_array($tokens[$i][0], [T_STRING, T_NS_SEPARATOR])) {
						$namespace .= $tokens[$i][1];
					}
				}
				
				continue;
			}
			
			if (is_array($tokens[$i]) && $tokens[$i][0] === T_CLASS) {
				while ($tokens[++$i] !== '{') {
					if (is_array($tokens[$i]) && $tokens[$i][0] === T_STRING) {
						$className .= $tokens[$i][1];
						break; //Classname is always one word
					}
				}
			}
		}
		
		return $namespace !== '' ? $namespace.'\\'.$className : $className;
	}
	
	public function implementsCommandInterface(string $phpFilePath):bool {
		//@todo add missing case: Class extends a class that implements this interface
		//@todo add missing case: Class implements an interface that extends this interface
		
		$contents = file_get_contents($phpFilePath);
		
		$interfaces = [];
		
		$includesInterface = false;
		$alias             = 'CommandInterface';
		//Go through each token and evaluate it as necessary
		$tokens     = token_get_all($contents);
		$tokenCount = count($tokens);
		for ($i = 0; $i < $tokenCount; $i++) {
			//Check if the FQN of our command interface is used in the code
			if ($includesInterface === false && is_array($tokens[$i]) && $tokens[$i][0] === T_USE) {
				$usedFQN = '';
				//Current token is `namespace` the actual namespace starts at the next one
				while ($tokens[++$i] !== ';') {
					//Add all the namespace tokens until we hit a ;
					if (is_array($tokens[$i])) {
						$token = $tokens[$i][0];
						if (in_array($token, [T_STRING, T_NS_SEPARATOR])) {
							$usedFQN .= $tokens[$i][1];
						}
						
						if ($tokens[$i][0] === T_AS) {
							$i     += 2; //next token is a whitespace, after that it's the alias
							$alias = $tokens[$i][1];
						}
					}
				}
				$includesInterface = ($usedFQN === CommandInterface::class);
				continue;
			}
			
			//Get a list of all implemented interfaces
			if (is_array($tokens[$i]) && $tokens[$i][0] === T_IMPLEMENTS) {
				while ($tokens[++$i] !== '{') {
					if (is_array($tokens[$i]) && $tokens[$i][0] === T_NS_SEPARATOR) {
						$interfaceName = '';
						while($tokens[++$i] !== ',') {
							$interfaceName .= $tokens[$i][1];
						}
						$interfaces[] = $interfaceName;
						continue;
					}
					
					if (is_array($tokens[$i]) && in_array($tokens[$i][0], [T_STRING, T_NS_SEPARATOR])) {
						$interfaces[] = $tokens[$i][1];
					}
				}
				continue;
			}
		}
		
		if (in_array(CommandInterface::class, $interfaces, true)) {
			return true;
		}
		
		if ($includesInterface && in_array($alias, $interfaces, true)) {
			return true;
		}
		
		return false;
	}
}