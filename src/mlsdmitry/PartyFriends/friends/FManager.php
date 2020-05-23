<?php


namespace mlsdmitry\PartyFriends\friends;


use DateTime;
use mlsdmitry\LangAPI\Lang;
use mlsdmitry\PartyFriends\friends\obj\Request;
use mlsdmitry\PartyFriends\PartyFriends;
use mlsdmitry\PartyFriends\PartyFriends as PF;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\Player;
use pocketmine\Server;
use mlsdmitry\PartyFriends\Utils;

class FManager implements Listener
{
    private static $requests = [];
    const IS_OFFLINE = 0;
    const ALREADY_FRIEND = 1;
    const SUCCESS = 2;
    const DONT_HAVE_REQUESTS = 3;
    const INVALID_NAME = 4;
    const REQUEST_EXPIRED = 5;
    const NOT_YOUR_FRIEND = 6;
    const DONT_HAVE_FRIENDS = 7;

    public function onJoin(PlayerJoinEvent $event)
    {
        $p = $event->getPlayer();
        if (!PF::$friends_db->exists(Utils::name($p))) {
            PF::$friends_db->set(Utils::name($p));
            PF::$friends_db->save();
        }
        if (!PF::$associations->exists(Utils::name($p))) {
            PF::$associations->setNested(Utils::name($p), []);
            PF::$associations->save();
        }
    }

    /**
     * @param string | Player $p
     * @param string | Player $p2
     * @return bool
     */
    public static function has($p, $p2)
    {
        return in_array(Utils::name($p2), PF::$associations->getNested(Utils::name($p)));
    }

    /**
     * @param Player $requester
     * @param string $awaiter
     * @return int
     */
    public static function request(Player $requester, $awaiter)
    {
        if (self::has($requester, $awaiter))
            return self::ALREADY_FRIEND;

        $awaiter_obj = PartyFriends::getCachedPlayer($awaiter);
        if (is_null($awaiter_obj))
            return self::IS_OFFLINE;

        $r = new Request();
        $r->setRequester($requester);
        $r->setAwaiter($awaiter_obj);
        self::$requests[Utils::name($awaiter)][Utils::name($requester)] = $r;
        return self::SUCCESS;
    }

    /**
     * @param Player $awaiter
     * @param string $requester
     * @return int
     */
    public static function accept(Player $awaiter, string $requester)
    {
        if (self::has($requester, $awaiter))
            return self::ALREADY_FRIEND;

        if (!isset(self::$requests[Utils::name($awaiter)]))
            return self::DONT_HAVE_REQUESTS;

        if (!isset(self::$requests[Utils::name($awaiter)][Utils::name($requester)]))
            return self::INVALID_NAME;


        /** @var Request $r */
        $r = self::$requests[Utils::name($awaiter)][Utils::name($requester)];

        $now = new DateTime();

        if ($now->diff($r->getDate())->i > PF::make()->getConfig()->get('friend_request_expire'))
            return self::REQUEST_EXPIRED;

        self::add($r->getRequester(), $r->getAwaiter());
        return self::SUCCESS;
    }

    /**
     * @param string | Player $p1
     * @param string | Player $p2
     * @return int
     */
    public static function add($p1, $p2)
    {
        if (self::has($p1, $p2))
            return self::ALREADY_FRIEND;

        $p1_friends = PF::$associations->getNested(Utils::name($p1));
        $p1_friends[] = Utils::name($p2);
        PF::$associations->setNested(Utils::name($p1), $p1_friends);

        $p2_friends = PF::$associations->getNested(Utils::name($p2));
        $p2_friends[] = Utils::name($p1);
        PF::$associations->setNested(Utils::name($p2), $p2_friends);

        PF::$associations->save();

        return self::SUCCESS;
    }

    /**
     * @param string | Player $p1
     * @param string | Player $p2
     * @return int
     */
    public static function remove($p1, $p2)
    {
        $p1_friends = PF::$associations->getNested(Utils::name($p1));

        if (in_array(Utils::name($p2), $p1_friends))
            unset($p1_friends[array_search(Utils::name($p1), $p1_friends)]);
        else
            return self::NOT_YOUR_FRIEND;

        PF::$associations->setNested(Utils::name($p1), $p1_friends);

        $p2_friends = PF::$associations->getNested(Utils::name($p2));

        if (in_array(Utils::name($p1), $p2_friends))
            unset($p2_friends[array_search(Utils::name($p2), $p2_friends)]);

        PF::$associations->setNested(Utils::name($p2), $p2_friends);

        PF::$associations->save();
        return self::SUCCESS;
    }

    /**
     * @param string | Player $awaiter
     * @param string | Player $requester
     * @return int
     */
    public static function deny($awaiter, $requester)
    {
        if (!isset(self::$requests[Utils::name($awaiter)]))
            return self::DONT_HAVE_REQUESTS;

        if (!isset(self::$requests[Utils::name($awaiter)][Utils::name($requester)]))
            return self::INVALID_NAME;

        unset(self::$requests[Utils::name($awaiter)][Utils::name($requester)]);
        return self::SUCCESS;
    }


    /**
     * @param string | Player $p
     * @return int | array
     */
    public static function flist($p)
    {
        if (PF::$associations->getNested(Utils::name($p)) === [])
            return self::DONT_HAVE_FRIENDS;
        return PF::$associations->getNested(Utils::name($p));
    }

    /**
     * @param string | Player $p
     * @return int | Request[]
     */
    public static function rlist($p)
    {
        if (!isset(self::$requests[Utils::name($p)]))
            return self::DONT_HAVE_REQUESTS;
        return self::$requests[Utils::name($p)];
    }
}