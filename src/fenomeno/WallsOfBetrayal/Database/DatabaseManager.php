<?php
namespace fenomeno\WallsOfBetrayal\Database;

use fenomeno\WallsOfBetrayal\Database\Contrasts\PlayerRepositoryInterface;
use fenomeno\WallsOfBetrayal\Database\Contrasts\Statements;
use fenomeno\WallsOfBetrayal\Database\Repository\PlayerRepository;
use fenomeno\WallsOfBetrayal\libs\poggit\libasynql\DataConnector;
use fenomeno\WallsOfBetrayal\libs\poggit\libasynql\libasynql;
use fenomeno\WallsOfBetrayal\Main;
use Throwable;

/***
 * @mixin DataConnector
 */
class DatabaseManager
{

    private DataConnector $database;

    private PlayerRepositoryInterface $playerRepository;

    public function __construct(
        private readonly Main $main
    ){
        try {
            $this->database = libasynql::create($this->main, $this->main->getConfig()->get("database"), [
                "sqlite" => "queries/sqlite.sql",
                "mysql"  => "queries/mysql.sql"
            ]);

            $this->database->executeGeneric(Statements::INIT_PLAYERS, [], function (){
                $this->main->getLogger()->info("§aTable `players` has been successfully init");
            });

            $this->playerRepository = new PlayerRepository($this->main);
        } catch (Throwable $e){
            $this->main->getLogger()->error("§cAn error occurred while init database: " . $e->getMessage());
        }
    }

    public function getPlayerRepository(): PlayerRepositoryInterface
    {
        return $this->playerRepository;
    }

    public function __call(string $name, array $arguments)
    {
        return call_user_func_array([$this->database, $name], $arguments);
    }

}