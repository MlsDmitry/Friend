<?php


namespace mlsdmitry\PartyFriends\friends;


use mlsdmitry\LangAPI\Lang;
use mlsdmitry\PartyFriends\party\PManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as C;

class FCommands extends Command
{

    public function execute(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!isset($args[0]))
            $sender->sendMessage($this->getUsage());
        $p = Server::getInstance()->getPlayer($sender->getName());
        switch ($args[0]) {
            case "accept":
                if (!isset($args[1])) {
                    $sender->sendMessage($this->getUsage());
                    return;
                }
                $cause = FManager::accept($p, $args[1]);
                if ($cause === FManager::ALREADY_FRIEND)
                    $sender->sendMessage(Lang::get('f-already-friend', ['nickname' => $args[1]], $p));
                elseif ($cause === FManager::DONT_HAVE_REQUESTS)
                    $sender->sendMessage(Lang::get('f-dont-have-requests', [], $p));
                elseif ($cause === FManager::INVALID_NAME)
                    $sender->sendMessage(Lang::get('f-invalid-name', ['nickname' => $args[1]], $p));
                elseif ($cause === FManager::REQUEST_EXPIRED)
                    $sender->sendMessage(Lang::get('f-request-expired', ['nickname' => $args[1]], $p));
                elseif ($cause === FManager::SUCCESS)
                    $sender->sendMessage(Lang::get('f-success-accept', ['nickname' => $args[1]], $p));
                break;
            case "add":
                if (!isset($args[1])) {
                    $sender->sendMessage($this->getUsage());
                    return;
                }
                $cause = FManager::request($p, $args[1]);
                if ($cause === FManager::ALREADY_FRIEND)
                    $sender->sendMessage(Lang::get('f-already-friend', ['nickname' => $args[1]], $p));
                elseif ($cause === FManager::IS_OFFLINE)
                    $sender->sendMessage(Lang::get('f-player-offline', ['nickname' => $args[1]], $p));
                elseif ($cause === FManager::SUCCESS)
                    $sender->sendMessage(Lang::get('f-success-request', ['nickname' => $args[1]], $p));
                break;
            case "deny":
                if (!isset($args[1])) {
                    $sender->sendMessage($this->getUsage());
                    return;
                }
                $cause = FManager::deny($p, $args[1]);
                if ($cause === FManager::DONT_HAVE_REQUESTS)
                    $sender->sendMessage(Lang::get('f-dont-have-requests', [], $p));
                elseif ($cause === FManager::INVALID_NAME)
                    $sender->sendMessage(Lang::get('f-invalid-name', ['nickname' => $args[1]], $p));
                break;
            case "help":
                $sender->sendMessage($this->getUsage());
                break;
            case "list":
//                if (!isset($args[1])) {
//                    $sender->sendMessage($this->getUsage());
//                    return;
//                }
                $cause = FManager::flist($p);
                if ($cause === FManager::DONT_HAVE_FRIENDS)
                    $sender->sendMessage(Lang::get('f-dont-have-friends', [], $p));
                elseif (is_array($cause)) {
                    $friends = [];
                    /**
                     * @var string $name
                     */
                    foreach ($cause as $name) {
                        $p = Server::getInstance()->getPlayer($name);
                        if ($p->isOnline())
                            $friends[] = C::RED . $p->getName() . C::GREEN . ' [ONLINE]' . C::RESET;
                        else
                            $friends[] = C::GRAY . $p->getName() . C::GRAY . ' [OFFLINE]' . C::RESET;

                    }
                    $sender->sendMessage(Lang::get('f-friend-list', ['friends' => implode(', ', $friends)], $p));
                }
                break;
            case "remove":
                if (!isset($args[1])) {
                    $sender->sendMessage($this->getUsage());
                    return;
                }
                $cause = FManager::remove($p, $args[1]);
                if ($cause === FManager::NOT_YOUR_FRIEND)
                    $sender->sendMessage(Lang::get('f-not-your-friend', ['nickname' => $args[1]], $p));
                elseif ($cause === FManager::SUCCESS)
                    $sender->sendMessage(Lang::get('f-success-remove', ['nickname' => $args[1]], $p));
                break;
            case "requests":
//                if (!isset($args[1])) {
//                    $sender->sendMessage($this->getUsage());
//                    return;
//                }
                $cause = FManager::rlist($p);
                if ($cause === FManager::DONT_HAVE_REQUESTS)
                    $sender->sendMessage(Lang::get('f-dont-have-requests', [], $p));
                elseif (is_array($cause)) {
                    $requests = [];
                    /**
                     * @var string $requester
                     * @var Player $obj
                     */
                    foreach ($cause as $requester => $_) {
                        $requests[] = $requester;
                    }
                    $sender->sendMessage(Lang::get('f-requests-list', ['requests' => implode(', ', $requests)], $p));
                }
                break;
            case "toggle":
                //
                break;
        }
    }
}