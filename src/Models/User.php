<?php

namespace PenguinAstronaut\UserTable\Models;

use PDO;
use PenguinAstronaut\UserTable\Core\DB;

class User
{
    public const ORDER_FIELDS_AVAILABLE = [
        'id',
        'login',
        'name',
        'email'
    ];
    protected PDO $pdo;

    public function __construct()
    {
        $this->pdo = DB::getInstance();
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    }

    /**
     * Return list of users
     *
     * @param int    $limit
     * @param int    $offset
     * @param string $orderBy
     * @param string $orderDirection
     *
     * @return array
     */
    public function getAll(
        int $limit,
        int $offset,
        string $orderBy = 'id',
        string $orderDirection = 'ASC'
    ): array {
        $queryAll = 'SELECT count(id) FROM users';
        $stmt  = $this->pdo->query($queryAll);
        $totalCount = $stmt->fetchColumn();

        $orderBy = in_array($orderBy, self::ORDER_FIELDS_AVAILABLE)
            ? $orderBy
            : 'id';

        $orderDirection = strtoupper($orderDirection);
        $orderDirection = in_array($orderDirection, ['ASC', 'DESC'])
            ? $orderDirection
            : 'ASC';

        $query = "SELECT * FROM users ORDER BY $orderBy $orderDirection LIMIT $offset, $limit";
        $stmt = $this
            ->pdo
            ->query($query);
        $stmt->execute();
        $userList = $stmt->fetchAll() ?: [];

        return [
            'users' => $userList,
            'usersCount' => $totalCount
        ];
    }

    public function insertOrUpdate(array $userList): array
    {
        $insertedRowsCount = 0;
        $updatedRowsCount = 0;
        $updateTime = date('Y-m-d H:i:s');

        foreach ($userList as $user) {
            $user = $this->fillEmptyData($user);
            $user['updatetime'] = $updateTime;
            if (!$exUser = $this->getByLogin($user['login'])) {
                $insertedRowsCount++;
                $this->insert($user);
            } else {
                $updatedRowsCount++;
                $this->update($exUser['id'], $user);
            }
        }

        $deleteCount = $this->deleteByUpdateTime($updateTime);

        return [
            'insertCount' => $insertedRowsCount,
            'updateCount' => $updatedRowsCount,
            'deleteCount' => $deleteCount,
            'totalCount' => $insertedRowsCount + $updatedRowsCount + $deleteCount
        ];
    }

    /**
     * Get user by login
     *
     * @param string $login
     *
     * @return array|null
     */
    public function getByLogin(string $login): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE login=?');
        $stmt->execute([$login]);

        return $stmt->fetch() ?: null;
    }

    /**
     * Update user by id
     *
     * @param int   $id
     * @param array $data
     *
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $data = $this->fillEmptyData($data);
        $query = 'UPDATE users SET login = :login, password = :password, name = :name, email = :email, '
            . 'updatetime = :updatetime  WHERE id = :userid';
        $stmt = $this->pdo->prepare($query);
        $data['userid'] = $id;

        return $stmt->execute($data);
    }

    /**
     * Create user
     *
     * @param array $data
     *
     * @return int
     */
    public function insert(array $data): int
    {
        $query = 'INSERT INTO users(login, password, name, email, updatetime) '
            . 'VALUES(:login, :password, :name, :email, :updatetime)';

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($data);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Delete rows if not equal updateTime
     *
     * @param string $updateTime
     *
     * @return int count deleted rows
     */
    public function deleteByUpdateTime(string $updateTime): int
    {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE updatetime!=?');
        $stmt->execute([$updateTime]);

        return $stmt->rowCount();
    }

    /**
     * Fill empty field to null
     *
     * @param array $data
     *
     * @return array
     */
    private function fillEmptyData(array $data): array
    {
        $data['email'] = $data['login'] . '@example.com';
        $data['name'] = $data['login'];

        return $data;
    }
}