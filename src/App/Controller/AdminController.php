<?php

namespace App\Controller;

use Psr\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Model\DataTable;
use App\Model\GameAccount;
use Exception;

class AdminController
{
    protected $container;

    // constructor receives container instance
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function AdminPage(Request $HttpRequest, Response $HttpResponse, array $HttpArgs)
    {
        AdminController::VerifyLogin();

        return $this->container->get('renderer')->render($HttpResponse, 'core/page-admin.phtml', []);
    }

    public function ListAccount(Request $HttpRequest, Response $HttpResponse, array $HttpArgs)
    {
        AdminController::VerifyLogin();
        $newResponse = $HttpResponse->withHeader('Content-type', 'application/json');
        $table = new DataTable("
            SELECT
                user_account.uid as uid,
                user_account.account as account_name,
                case when user_account.block_flag = 0 then 'No' else 'Yes' end as banned,
                convert(varchar, user_account.block_end_date, 101) as ban_expiry,
                convert(varchar, user_account.last_login, 101) as last_login,
                user_account.last_ip as last_ip,
                char_stats.inf as inf,
                char_count.num as num_characters,
                case when char_stats.Active > 0 then 'Yes' else '-' end as active,
                char_stats.TimePlayed as online_time_this_session,
                char_stats.TotalTime as online_time_total,
                NULL as button
            FROM cohauth.dbo.user_account
            LEFT JOIN (SELECT Ents.AuthId, SUM(CONVERT(BIGINT, ISNULL(Ents.InfluencePoints, 0))) as inf, SUM(CONVERT(BIGINT, ISNULL(Ents.TotalTime, 0))) as TotalTime, SUM(ISNULL(Ents.Active, 0)) as Active, SUM(CONVERT(BIGINT, ISNULL(Ents.TimePlayed, 0))) as TimePlayed FROM cohdb.dbo.Ents GROUP BY Ents.AuthId) char_stats
            ON user_account.uid = char_stats.AuthId
            LEFT JOIN (SELECT Ents.AuthId, count(*) as num FROM cohdb.dbo.Ents GROUP BY Ents.AuthId) char_count
            ON user_account.uid = char_count.AuthId");

        return $newResponse->write($table->GetJSON());
    }

    public function AdminAccount(Request $HttpRequest, Response $HttpResponse, array $HttpArgs)
    {
        AdminController::VerifyLogin();
        $account = new GameAccount($HttpArgs['uid']);

        return $this->container->get('renderer')->render($HttpResponse, 'core/page-admin-account.phtml', [
            'username' => $account->GetUsername(),
            'uid' => $account->GetUID(),
        ]);
    }

    public function ListCharacter(Request $HttpRequest, Response $HttpResponse, array $HttpArgs)
    {
        AdminController::VerifyLogin();
        $newResponse = $HttpResponse->withHeader('Content-type', 'application/json');
        $table = new DataTable('
            SELECT
            	Ents.ContainerId,
            	Ents.Name,
            	Ents.StaticMapId,
            	Ents.Level,
            	Ents.ExperiencePoints,
            	Ents.InfluencePoints,
            	convert(varchar, ents.LastActive, 101) as LastActive,
            	ents.AccessLevel,
            	null as button
            FROM cohdb.dbo.ents
            WHERE AuthId = ?', array($HttpArgs['uid']));

        return $newResponse->write($table->GetJSON());
    }

    public static function VerifyLogin()
    {
        if (!isset($_SESSION['account'])) {
            throw new Exception('You must be logged in to access this page.');
        }

        if (!$_SESSION['account']->IsAdmin()) {
            throw new Exception('You must be an administrator to access this page.');
        }
    }
}
