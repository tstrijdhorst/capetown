# A keybase.io bot framework

**This package is currently in alpha mode. It's fully functional but the API implementation is far from complete. Accepting PRs for further implementation.** 

This is a small framework for writing keybase.io bots in PHP. 

# Usage
* Clone this repo
* Install the commands you want with composer
* Add the FQN of the commandclass to `EnabledCommands.php`
* **(If Necessary)** Copy the `name.env.dist` file from the plugin to `config/name.env` and fill in the required information
* Run `app/run.php` and you are good to go!

You can trigger the commands by sending a message to the bot prefixed with `@botname <commandname> (param1 param2 param3 ...)`.

## Architecture

##### Note
*While the author really likes to keep things separate there is nothing from stopping you to implement your plugin in a different way. As long as you implement the `CommandInterface` and you are able to add the FQN of your command to the `app/EnabledCommands.php` it will be fine.*

*For plugin development in might be handy to fork this framework and develop the plugin locally first. An example Command has been provided in `app/commands/ExampleCommand.php` to illustrate the design further.*

### The runner (this)
This is where you add the commands you want to enable via composer and run the actual bot with.
    
### [The core](https://github.com/tstrijdhorst/capetown-core)

This is all the core code that both the runner and all the command plugins depend on. In this way we ensure that both the runner and all the plugins never depend on eachother and each is free to do what they want as long as you implement the `CommmandInterface`.
    
### Official command plugins
* [Giphy Command](https://github.com/tstrijdhorst/capetown-giphy)
