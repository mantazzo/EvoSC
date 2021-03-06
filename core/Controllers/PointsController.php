<?php


namespace EvoSC\Controllers;


use EvoSC\Classes\ChatCommand;
use EvoSC\Classes\Hook;
use EvoSC\Classes\Log;
use EvoSC\Classes\Server;
use EvoSC\Classes\Template;
use EvoSC\Interfaces\ControllerInterface;
use EvoSC\Models\Player;

class PointsController implements ControllerInterface
{
    /**
     * @var int
     */
    private static int $originalPointsLimit = -1;

    /**
     * @var int
     */
    private static int $currentPointsLimit = -1;

    /**
     *
     */
    public static function init()
    {
    }

    /**
     * @param string $mode
     * @param bool $isBoot
     */
    public static function start(string $mode, bool $isBoot)
    {
        if (ModeController::isTimeAttack()) {
            return;
        }

        self::$originalPointsLimit = MatchSettingsController::getValueFromCurrentMatchSettings('S_PointsLimit');
        if (self::$originalPointsLimit == -1) {
            self::$originalPointsLimit = Server::getRoundPointsLimit()['CurrentValue'];
        }
        if (self::$currentPointsLimit == -1) {
            self::$currentPointsLimit = Server::getRoundPointsLimit()['NextValue'];
        }

        Hook::add('PlayerConnect', [self::class, 'playerConnect']);
        Hook::add('Maniaplanet.Podium_Start', [self::class, 'resetPointsLimit']);

        ChatCommand::add('//addpoints', [self::class, 'cmdAddPoints'], 'Add points to the points-limit.', 'manipulate_points');
    }

    /**
     * @param Player $player
     * @throws \EvoSC\Exceptions\InvalidArgumentException
     */
    public static function playerConnect(Player $player)
    {
        $points = self::$currentPointsLimit;
        Template::show($player, 'Helpers.update-points-limit', compact('points'));
    }

    /**
     * @param Player $player
     * @param $cmd
     * @param $points
     */
    public static function cmdAddPoints(Player $player, $cmd, $points)
    {
        $points = intval($points);
        self::increasePointsLimit($points);
        infoMessage(secondary($player), ' increased the points-limit by ' . secondary($points . ' points'))->sendAll();
    }

    /**
     * @param int $points
     */
    public static function increasePointsLimit(int $points)
    {
        $modeScriptSettings = Server::getModeScriptSettings();
        self::$currentPointsLimit = ($newLimit = $modeScriptSettings['S_PointsLimit'] + $points);
        $modeScriptSettings['S_PointsLimit'] = $newLimit;
        Server::setModeScriptSettings($modeScriptSettings);
        Log::info('Increased points-limit to ' . $newLimit);
        self::sendUpdatedPointsLimit($newLimit);
    }

    /**
     *
     */
    public static function resetPointsLimit()
    {
        $modeScriptSettings = Server::getModeScriptSettings();
        $modeScriptSettings['S_PointsLimit'] = self::$originalPointsLimit;
        Server::setModeScriptSettings($modeScriptSettings);
        self::sendUpdatedPointsLimit(self::$originalPointsLimit);
        self::$currentPointsLimit = self::$originalPointsLimit;
    }

    /**
     * @return int
     */
    public static function getCurrentPoints(): int
    {
        $modeScriptSettings = Server::getModeScriptSettings();

        if (array_key_exists('S_PointsLimit', $modeScriptSettings)) {
            return Server::getModeScriptSettings()['S_PointsLimit'];
        }

        return Server::getRoundPointsLimit()['CurrentValue'];
    }

    /**
     * @return int
     */
    public static function getOriginalPointsLimit(): int
    {
        return self::$originalPointsLimit;
    }

    /**
     * @param int $points
     */
    private static function sendUpdatedPointsLimit(int $points)
    {
        Template::showAll('Helpers.update-points-limit', compact('points'));
    }

    /**
     * @return array
     */
    public static function getPointsRepartition(): array
    {
        $points = Server::getModeScriptSettings()['S_PointsRepartition'];

        //modescript: Trackmania.GetPointsRepartition

        if ($points) {
            $parts = explode(',', $points);
            return array_map(function ($point) {
                return intval($point);
            }, $parts);
        }

        return [10, 6, 4, 3, 2, 1];
    }
}