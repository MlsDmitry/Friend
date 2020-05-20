<?php


namespace mlsdmitry\PartyFriends;


use mlsdmitry\LangAPI\Lang;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Player;
use pocketmine\Server;

class PManager implements Listener
{
    private static $parties = [];
    private static $await_accept = [];

    const OWNER = 0;
    const FOLLOWER = 1;
    const NOT_FOUND = 2;

    const DONT_HAVE_PARTY = 3;
    const IS_OFFLINE = 4;

    const AWAIT = 5;


    const INVITE_COMMAND = 6;
    const PROMOTE_COMMAND = 7;

    public static function hasParty(Player $player)
    {
        $uuid = $player->getUniqueId()->toString();

        return isset(self::$parties[$uuid]) ? self::OWNER : self::NOT_FOUND;
    }

    public static function searchFollower(string $follower_uuid)
    {
        foreach (self::$parties as $owner_uuid => $followers) {
            if (isset($uuid, $followers))
                return $owner_uuid;
        }
        return self::NOT_FOUND;
    }


    #----------------------------------------------------------------------------------------------------------
    public static function remove(Player $owner, string $follower_name)
    {
        $follower = Server::getInstance()->getPlayer($follower_name);
        if (is_null($follower)) {
            $owner->sendMessage(Lang::get('player-not-found', ['nickname' => $follower->getName()], $owner));
            return;
        }
        if (self::hasParty($owner) === self::OWNER) {
            $result = self::searchFollower($follower->getUniqueId()->toString());
            if ($result === self::NOT_FOUND) {
                $owner->sendMessage(Lang::get('player-not-found', ['nickname' => $follower->getName()], $owner));
                return;
            } elseif ($result !== $owner->getUniqueId()->toString()) {
                $owner->sendMessage(Lang::get('follower-is-not-in-your-party', ['nickname' => $follower->getName()], $owner));
                return;
            }
            $follower->sendMessage(Lang::get('removed-from-party', [], $follower));
            unset(self::$parties[$owner->getUniqueId()->toString()][$follower->getUniqueId()->toString()]);
        } else {
            $owner->sendMessage(Lang::get('dont-have-party', [], $owner));
        }
    }

    public static function try_promote_or_invite(Player $owner, string $follower_name, int $command)
    {
        $owner_uuid = $owner->getUniqueId()->toString();
        $follower_player = Server::getInstance()->getPlayer($follower_name);
        if (!$follower_player->isOnline()) {
            $owner->sendMessage(Lang::get('player-is-offline', ['nickname' => $follower_name], $owner));
        }
        $follower_uuid = $follower_player->getUniqueId()->toString();

        $follower_player->sendMessage(Lang::get('promote-request', [], $follower_player));

        self::$await_accept[$follower_uuid][strtolower(trim($owner->getName()))] = [
            'owner_uuid' => $owner_uuid,
            'owner_obj' => $owner,
            'date' => new \DateTime(),
            'type' => $command
        ];
        /*
        $await_accept[1234-1234][MlsDmitry] =
        [
            'owner_uuid' => 1234-1235,
            'owner_obj' => PlayerObj,
            'date' => DateTime,
            'type' => promote | invite
        ]
        */
    }


    public function onChat(PlayerChatEvent $event): void
    {
        $p = $event->getPlayer();
        $uuid = $p->getUniqueId()->toString();
        $message = trim($event->getMessage());
        $args = explode(' ', $message);
        if (!isset($args[0])) return; //! just pass
        if (trim($args[0]) !== 'promoteme' or trim($args[0]) !== 'acceptinvite') return; //! we looking only for promoteme message
        if (!isset($args[1])) { //! if player forgot to indicate promoter's name
            $p->sendMessage(Lang::get('name-missed', [], $p));
            return;
        }
        $nick_name = strtolower(trim($args[1]));
        if (!isset(self::$await_accept[$uuid][$nick_name])) { //! if indicated name not found
            $p->sendMessage(Lang::get('player-not-found', [], $p));
            return;
        }

        // TODO remove from older parties!
        if (trim($args[0]) === 'promoteme') {
            if (self::$await_accept[$uuid][$nick_name]['type'] !== self::PROMOTE_COMMAND) {
                $p->sendMessage(Lang::get('no-promotes-found', [], $p));
                return;
            }
            $data = self::$await_accept[$uuid][$nick_name];

            $dt = new \DateTime();
            if ($dt->diff($data['date'])->i > PartyFriends::make()->getConfig()->get('await-accept')) {
                $p->sendMessage(Lang::get('accept-expires', [], $p));
                return;
            }

            self::disband($p);


            if (self::hasParty($data['owner_obj']) === self::NOT_FOUND) {
                $data['owner_obj']->sendMessage(
                    Lang::get('party-with-name-created', ['owner' => $p->getName()], $data['owner_obj']));
            }
            self::$parties[$uuid][$data['owner_uuid']] = $data['owner_obj'];
            $p->sendMessage(Lang::get('promote-successful', [], $p));

            unset(self::$await_accept[$uuid][$nick_name]);

        } elseif (trim($args[1]) === 'acceptinvite') {
            if (self::$await_accept[$uuid][$nick_name]['type'] !== self::INVITE_COMMAND) {
                $p->sendMessage(Lang::get('no-invites-found', [], $p));
                return;
            }
            $data = self::$await_accept[$uuid][$nick_name];

            self::disband($p);

            if (self::hasParty($data['owner_obj']) === self::NOT_FOUND) {
                $data['owner_obj']->sendMessage(
                    Lang::get('party-with-name-created'), ['follower' => $p->getName()], $data['owner_obj']);
            }
            self::$parties[$data['owner_uuid']][$uuid] = $p;
            $p->sendMessage(Lang::get('invite-successful', [], $p));

            unset(self::$await_accept[$uuid][$nick_name]);
        }
    }

    public function onQuit(PlayerQuitEvent $event)
    {
        $p = $event->getPlayer();
        $uuid = $p->getUniqueId()->toString();
        if (isset(self::$parties[$uuid])) {
            self::disband($p);
            unset(self::$parties[$uuid]);
        }
        if (isset(self::$await_accept[$uuid])) {
            unset(self::$await_accept[$uuid]);
        }
    }

    public static function disband(Player $owner)
    {
        if (self::hasParty($owner) === self::OWNER) {
            foreach (self::$parties[$owner->getUniqueId()->toString()] as $follower_uuid => $follower_obj) {
                $follower_obj->sendMessage(Lang::get('party-disbanded', [], $follower_obj));
            }
        }
    }
}