# A keybase.io bot framework

This is a small framework for writing keybase.io bots in PHP. 

# Usage
* Install the commands you want with composer
* Add the FQN of the commandclass to `$enabledCommandClasses` in `run.php`
* Copy the `name.env.dist` file from the plugin to `config/` and fill in the required information
* Run `run.php` and you are good to go!

## Architecture

##### Note
While the author really likes to keep things seperate there is nothing from stopping you to implement your plugin in a different way. As long as you implement the `CommandInterface` and you are able to add the FQN of your command to the `$enabledCommandClasses` it will be fine.

### The runner (this)
This is where you add the commands you want to enable via composer and run the actual bot with.
    
### [The core](https://github.com/tstrijdhorst/capetown-core)

This is all the core code that both the runner and all the command plugins depend on. In this way we ensure that both the runner and all the plugins never depend on eachother and each is free to do what they want as long as you implement the `CommmandInterface`
    
### Official command plugins
* [Giphy Command](https://github.com/tstrijdhorst/capetown-giphy)
