<?php


namespace mlsdmtiry\PartyFriends;


use CortexPE\Commando\args\BaseArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseCommand;
use pocketmine\command\CommandSender;

class FCommands extends BaseCommand
{

    protected function prepare(): void
    {
        //! /f accept NickName
        $this->registerArgument(0, new RawStringArgument("accept", false));
        //! /f add NickName
        $this->registerArgument(0, new RawStringArgument("add", false));
        //! /f deny NickName
        $this->registerArgument(0, new RawStringArgument("deny", false));
        //! /f help
        $this->registerArgument(0, new RawStringArgument("help", false));
        //! /f list page
        $this->registerArgument(0, new RawStringArgument("list", false));
        //! /f remove NickName
        $this->registerArgument(0, new RawStringArgument("remove", false));
        //! /f requests page
        $this->registerArgument(0, new RawStringArgument("requests", false));
        //! /f toggle
        $this->registerArgument(0, new RawStringArgument("toggle", false));


        $this->registerArgument(1, new RawStringArgument("nick_name", false));
        $this->registerArgument(1, new RawStringArgument("page", false));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!isset($args[0]))
            $sender->sendMessage($this->getUsage());
        switch ($args[0]) {
            case "accept":
                if (!isset($args[1]))
                    $sender->sendMessage($this->getUsage());
                break;
            case "add":
                if (!isset($args[1]))
                    $sender->sendMessage($this->getUsage());
                break;
            case "deny":
                if (!isset($args[1]))
                    $sender->sendMessage($this->getUsage());
                break;
            case "help":
                $sender->sendMessage($this->getUsage());
                break;
            case "list":
                if (!isset($args[1]))
                    $sender->sendMessage($this->getUsage());
                break;
            case "remove":
                if (!isset($args[1]))
                    $sender->sendMessage($this->getUsage());
                break;
            case "requests":
                if (!isset($args[1]))
                    $sender->sendMessage($this->getUsage());
                break;
            case "toggle":
                //
                break;
        }
    }
}