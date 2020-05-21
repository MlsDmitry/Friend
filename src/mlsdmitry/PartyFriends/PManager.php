<?php


namespace mlsdmitry\PartyFriends;


use DateTime;
use mlsdmitry\LangAPI\Lang;
use mlsdmitry\PartyFriends\party\events\PartyDisbandEvent;
use mlsdmitry\PartyFriends\party\events\PlayerInvitedToPartyEvent;
use mlsdmitry\PartyFriends\party\events\PlayerPromoteEvent;
use mlsdmitry\PartyFriends\party\Party;
use mlsdmitry\PartyFriends\party\Request;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Player;
use pocketmine\Server;

class PManager implements Listener
{
    const DONT_HAVE_REQUESTS = 8;
    /**
     * @var array
     */
    private static $parties = [];
    private static $await_accept = [];

    const OWNER = 0;
    const FOLLOWER = 1;
    const NOT_FOUND = 2;

    const DONT_HAVE_PARTY = 3;
    const IS_OFFLINE = 4;
    const IS_NOT_OWNER = 5;
    const AWAIT = 6;

    const PARTY_EXPIRED = 7;


    /*
     ? PARTIES
     * Structure ->
     * [$owner_uuid] => $followers
      $parties[1234-1235-1236-1237] = [$nickname => [$follower_uuid, $follower_obj]]
     */
    /*
     ? AWAIT_ACCEPT
     * Structure ->
     * [$awaiter_uuid][$requester_nickname] =
     ! [
           'owner_uuid' => 1234-1235,
           'owner_obj' => PlayerObj,
           'date' => DateTime,
           'type' => promote | invite
     ! ]
     */


    //----------------------------------API CALLS----------------------------------


    /**
     * @param string | Player $owner_name
     * @return bool
     */
    public static function has_party($owner_name)
    {
        return isset(self::$parties[self::name($owner_name)]);
    }

    /**
     * @param string | Player $p
     * @param null $owner_name
     * @return bool
     */
    public static function is_follower($p, $owner_name = null)
    {
        if (is_null($owner_name)) {
            /**
             * @var string $owner_name
             * @var Party $party
             */
            foreach (self::$parties as $owner_name => $party) {
                if ($party->isFollower(self::uuid($p)))
                    return true;
            }
            return false;
        } else {
            /** @var Party $party */
            $party = self::$parties[self::name($owner_name)];
            $party->isFollower($p);
        }
    }


    /**
     * @param Player | string $p
     * @param array $spec
     * @return bool | int
     */
    public static function validate_player($p, array $spec = [])
    {
        if (is_object($p))
            if (!$p->isOnline())
                return self::IS_OFFLINE;
        if (in_array('has_party', $spec))
            if (!self::has_party($p))
                return self::DONT_HAVE_PARTY;
        return true;
    }

    /**
     * @param string | Player $p_name
     * @return string
     */
    public static function name($p_name)
    {
        return is_object($p_name) ? strtolower($p_name->getName()) : strtolower($p_name);
    }

    /**
     * @param string | Player $p_uuid
     * @return string
     */
    public static function uuid($p_uuid)
    {
        return is_object($p_uuid) ? $p_uuid->getName() : $p_uuid;
    }


    /**
     * @param Player $owner
     * @return bool | void
     */
    public static function disband(Player $owner)
    {
        $causes = self::validate_player($owner, ['has_party']);
        if (!$causes)
            return $causes;
        /** @var Party $party */
        $party = self::$parties[$owner->getName()];

        $ev = new PartyDisbandEvent($party);
        $ev->call();
        unset($party);
    }

    /**
     * @param Player $requester
     * @param $awaiter_name
     * @param int $type
     * @return bool|int
     */
    public static function await_accept(Player $requester, $awaiter_name, int $type)
    {
        $cause = self::validate_player($requester, ['has_party']);
        if ($cause === PManager::DONT_HAVE_PARTY) {
            self::registerParty($requester);
        } elseif ($cause === true) {
        } else {
            return $cause;
        }

        $awaiter = Server::getInstance()->getPlayer($awaiter_name);
        if (is_null($awaiter))
            return self::IS_OFFLINE;

        $r = new Request();
        $r->setRequester($requester);
        $r->setAwaiter($awaiter);
        $r->setType($type);
        self::$await_accept[strtolower($awaiter_name)] = $r;
        return true;
    }


    /**
     * @param string | Player $owner
     * @param Player $follower
     * @return bool|int
     */
    public static function addFollower($owner, Player $follower)
    {
        $cause = self::validate_player($owner, ['has_party']);
        if ($cause === PManager::DONT_HAVE_PARTY) {
            self::registerParty($owner);
        } elseif ($cause === true) {
        } else {
            return $cause;
        }
        /** @var Party $party */
        $party = self::$parties[self::name($owner)];
        $party->addFollower($follower);
        return true;
    }

    /**
     * @param Player $awaiter
     * @param string $requester_name
     * @return int
     */
    public static function accept(Player $awaiter, string $requester_name)
    {
        if (!isset(self::$await_accept[strtolower($awaiter->getName())]))
            return self::DONT_HAVE_REQUESTS;

        /** @var Request $request */
        $request = self::$await_accept[strtolower($awaiter->getName())];

        if (!strtolower($request->getRequester()->getName()) === strtolower($requester_name))
            return self::NOT_FOUND;

        $cause = self::validate_player($request->getRequester(), ['has_party']);
        if ($cause === PManager::DONT_HAVE_PARTY) {
            self::registerParty($request->getRequester());
        } elseif ($cause === true) {
        } else {
            return $cause;
        }
        $now = new DateTime();
        if ($now->diff($request->getDate())->i > PartyFriends::make()->getConfig()->get('party_expire'))
            return self::PARTY_EXPIRED;

        $type = $request->getType();
        /** @var Party $party */
        $party = self::$parties[strtolower($request->getRequester()->getName())];
        if ($type === Request::INVITE_COMMAND) {
            $ev = new PlayerInvitedToPartyEvent($party, $awaiter);
            $ev->call();
            return true;
        } elseif ($type === Request::PROMOTE_COMMAND) {
            $ev = new PlayerPromoteEvent($party, $awaiter);
            $ev->call();
            // Delete party
            unset(self::$parties[strtolower($party->getOwner()->getName())]);
            $followers = $party->getFollowers();
            if (isset($followers[$awaiter->getUniqueId()->toString()]))
                unset($followers[$awaiter->getUniqueId()->toString()]);
            $followers[$party->getOwner()->getUniqueId()->toString()] = $party->getOwner();
            self::registerParty($awaiter, $followers);
            return true;
        }
    }

    /**
     * @param Player $owner
     * @param array $followers
     */
    public static function registerParty(Player $owner, $followers = [])
    {
        self::$parties[strtolower($owner->getName())] = new Party($owner, $followers);
    }
//
//
//    public static function isFollower($p, Player $owner = null)
//    {
//        if ($owner === null) {
//            foreach (self::$parties as $owner_uuid => $followers) {
//                if (isset($followers[is_string($p) ? $p : $p->getName()]))
//                    return $owner_uuid;
//            }
//            return false;
//        } else {
//            return isset(self::$parties[$owner->getUniqueId()->toString()][$p->getName()]);
//        }
//    }
//
//    /**
//     * @param Player $p
//     */
//    public static function removeParty(Player $p)
//    {
//        $uuid = $p->getUniqueId()->toString();
//        unset(self::$parties[$uuid]);
//        echo 'removeParty' . PHP_EOL;
//        print_r(self::$parties);
//    }
//
//    /**
//     * @param Player $owner
//     * @return void
//     */
//    public static function disbandParty(Player $owner)
//    {
//        if (self::hasParty($owner)) {
//            self::removeParty($owner);
//        }
//        echo 'disbandParty' . PHP_EOL;
//        print_r(self::$parties);
//    }
//
//    public static function removeFollower($owner, Player $follower)
//    {
//        if (self::isFollower($owner, $follower)) {
//            unset(self::$parties[is_object($owner) ? $owner->getUniqueId()->toString() : $owner][$follower->getName()]);
//        }
//        echo 'removeFollower' . PHP_EOL;
//        print_r(self::$parties);
//    }
//
//
//    public static function addFollower($owner, Player $follower)
//    {
//        self::$parties[is_object($owner) ? $owner->getUniqueId()->toString() : $owner][$follower->getName()] = [$follower->getUniqueId()->toString(), $follower];
//        echo 'addFollower' . PHP_EOL;
//        print_r(self::$parties);
//    }
//
//    /**
//     * @param Player $owner
//     * @param Player $target
//     */
//    public static function changeOwner(Player $owner, Player $target)
//    {
//
//        if (self::hasParty($target))
//            self::disbandParty($target);
//        elseif ($owner_uuid = self::isFollower($target))
//            self::removeFollower($owner_uuid, $target);
//
//        $followers = self::$parties[$owner->getUniqueId()->toString()];
//        self::disbandParty($owner);
//        $followers[$owner->getName()] = [$owner->getUniqueId()->toString(), $owner];
//        self::$parties[$target->getUniqueId()->toString()] = $followers;
//    }
//
//    public static function awaitAccept($awaiter, Player $owner, int $command)
//    {
//        /*
//        ? AWAIT_ACCEPT
//        * Structure ->
//        * [$awaiter_uuid][$requester_nickname] =
//        ! [
//               'owner_uuid' => 1234-1235,
//               'owner_obj' => PlayerObj,
//               'date' => DateTime,
//               'type' => promote | invite
//        ! ]
//        */
//        self::$await_accept[is_object($awaiter) ? $awaiter->getUniqueId()->toString() : $awaiter][$owner->getName()] =
//            [
//                'owner_uuid' => $owner->getUniqueId()->toString(),
//                'owner_obj' => $owner,
//                'date' => new \DateTime(),
//                'type' => $command
//            ];
//        print_r(self::$await_accept);
//    }
//
//
//    public static function accept(Player $p, string $requester_nickname)
//    {
//        self::disbandParty($p);
//        print_r(self::$await_accept);
//        $data = self::$await_accept[$p->getUniqueId()->toString()][$requester_nickname];
//        if ($data['type'] === self::INVITE_COMMAND) {
//            self::addFollower($data['owner_obj'], $p);
//        } elseif ($data['type'] === self::PROMOTE_COMMAND) {
//            self::changeOwner($data['owner_obj'], $p);
//        }
//        print_r(self::$parties);
//    }


    //------------------------------END API CALLS----------------------------------


//    public static function hasParty(Player $player)
//    {
//        $uuid = $player->getUniqueId()->toString();
//
//        return isset(self::$parties[$uuid]) ? self::OWNER : self::NOT_FOUND;
//    }
//
//    public static function searchFollower(string $follower_uuid)
//    {
//        foreach (self::$parties as $owner_uuid => $followers) {
//            if (isset($uuid, $followers))
//                return $owner_uuid;
//        }
//        return self::NOT_FOUND;
//    }


    #----------------------------------------------------------------------------------------------------------
//    public static function remove(Player $owner, string $follower_name)
//    {
//        $follower = Server::getInstance()->getPlayer($follower_name);
//        if (is_null($follower)) {
//            $owner->sendMessage(Lang::get('player-not-found', ['nickname' => $follower->getName()], $owner));
//            return;
//        }
//        if (self::hasParty($owner) === self::OWNER) {
//            $result = self::searchFollower($follower->getUniqueId()->toString());
//            if ($result === self::NOT_FOUND) {
//                $owner->sendMessage(Lang::get('player-not-found', ['nickname' => $follower->getName()], $owner));
//                return;
//            } elseif ($result !== $owner->getUniqueId()->toString()) {
//                $owner->sendMessage(Lang::get('follower-is-not-in-your-party', ['nickname' => $follower->getName()], $owner));
//                return;
//            }
//            $follower->sendMessage(Lang::get('removed-from-party', [], $follower));
//            unset(self::$parties[$owner->getUniqueId()->toString()][$follower->getUniqueId()->toString()]);
//        } else {
//            $owner->sendMessage(Lang::get('dont-have-party', [], $owner));
//        }
//    }
//
//    public static function try_promote_or_invite(Player $owner, string $follower_name, int $command)
//    {
//        $owner_uuid = $owner->getUniqueId()->toString();
//        $follower_player = Server::getInstance()->getPlayer($follower_name);
//        if (!$follower_player->isOnline()) {
//            $owner->sendMessage(Lang::get('player-is-offline', ['nickname' => $follower_name], $owner));
//        }
//        $follower_uuid = $follower_player->getUniqueId()->toString();
//
//        $follower_player->sendMessage(Lang::get('promote-request', [], $follower_player));
//
//        self::$await_accept[$follower_uuid][strtolower(trim($owner->getName()))] = [
//            'owner_uuid' => $owner_uuid,
//            'owner_obj' => $owner,
//            'date' => new DateTime(),
//            'type' => $command
//        ];
//        /*
//        $await_accept[1234-1234][MlsDmitry] =
//        [
//            'owner_uuid' => 1234-1235,
//            'owner_obj' => PlayerObj,
//            'date' => DateTime,
//            'type' => promote | invite
//        ]
//        */
//    }


//    public function onChat(PlayerChatEvent $event): void
//    {
//        $p = $event->getPlayer();
//        $uuid = $p->getUniqueId()->toString();
//        $message = trim($event->getMessage());
//        $args = explode(' ', $message);
//        if (!isset($args[0])) return; //! just pass
//        if (trim($args[0]) !== 'promoteme' or trim($args[0]) !== 'acceptinvite') return; //! we looking only for promoteme message
//        if (!isset($args[1])) { //! if player forgot to indicate promoter's name
//            $p->sendMessage(Lang::get('name-missed', [], $p));
//            return;
//        }
//        $nick_name = strtolower(trim($args[1]));
//        if (!isset(self::$await_accept[$uuid][$nick_name])) { //! if indicated name not found
//            $p->sendMessage(Lang::get('player-not-found', [], $p));
//            return;
//        }
//
//        // TODO remove from older parties!
//        if (trim($args[0]) === 'promoteme') {
//            if (self::$await_accept[$uuid][$nick_name]['type'] !== self::PROMOTE_COMMAND) {
//                $p->sendMessage(Lang::get('no-promotes-found', [], $p));
//                return;
//            }
//            $data = self::$await_accept[$uuid][$nick_name];
//
//            $dt = new DateTime();
//            if ($dt->diff($data['date'])->i > PartyFriends::make()->getConfig()->get('await-accept')) {
//                $p->sendMessage(Lang::get('accept-expires', [], $p));
//                return;
//            }
//
//            self::disband($p);
//
//
//            if (self::hasParty($data['owner_obj']) === self::NOT_FOUND) {
//                $data['owner_obj']->sendMessage(
//                    Lang::get('party-with-name-created', ['owner' => $p->getName()], $data['owner_obj']));
//            }
//            self::$parties[$uuid][$data['owner_uuid']] = $data['owner_obj'];
//            $p->sendMessage(Lang::get('promote-successful', [], $p));
//
//            unset(self::$await_accept[$uuid][$nick_name]);
//
//        } elseif (trim($args[1]) === 'acceptinvite') {
//            if (self::$await_accept[$uuid][$nick_name]['type'] !== self::INVITE_COMMAND) {
//                $p->sendMessage(Lang::get('no-invites-found', [], $p));
//                return;
//            }
//            $data = self::$await_accept[$uuid][$nick_name];
//
//            self::disband($p);
//
//            if (self::hasParty($data['owner_obj']) === self::NOT_FOUND) {
//                $data['owner_obj']->sendMessage(
//                    Lang::get('party-with-name-created'), ['follower' => $p->getName()], $data['owner_obj']);
//            }
//            self::$parties[$data['owner_uuid']][$uuid] = $p;
//            $p->sendMessage(Lang::get('invite-successful', [], $p));
//
//            unset(self::$await_accept[$uuid][$nick_name]);
//        }
//    }

//    public function onQuit(PlayerQuitEvent $event)
//    {
//        $p = $event->getPlayer();
//        $uuid = $p->getUniqueId()->toString();
//        if (isset(self::$parties[$uuid])) {
//            self::disband($p);
//            unset(self::$parties[$uuid]);
//        }
//        if (isset(self::$await_accept[$uuid])) {
//            unset(self::$await_accept[$uuid]);
//        }
//    }
//
//    public static function disband(Player $owner)
//    {
//        if (self::hasParty($owner) === self::OWNER) {
//            foreach (self::$parties[$owner->getUniqueId()->toString()] as $follower_uuid => $follower_obj) {
//                $follower_obj->sendMessage(Lang::get('party-disbanded', [], $follower_obj));
//            }
//        }
//    }
    /**
     * @return array
     */
    public static function getParties(): array
    {
        return self::$parties;
    }
}