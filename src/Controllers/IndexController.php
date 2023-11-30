<?php

namespace PenguinAstronaut\UserTable\Controllers;

use PenguinAstronaut\UserTable\Core\Controller;
use PenguinAstronaut\UserTable\Models\User;

class IndexController extends Controller
{
    const USERS_PAGE_COUNT = 20;

    public function index()
    {
        $curPage = $_GET['page'] ?? 1;
        $curPage = (int)$curPage ?: 1;

        $orderBy = $_REQUEST['orderBy'] ?? 'id';

        $orderDirection = $_REQUEST['orderDirection'] ?? 'ASC';

        $user = new User();

        $uploadInfo = null;
        $error = null;
        if ($file = $_FILES['userImportFile'] ?? null) {
            @['error' => $error, 'info' => $uploadInfo] = $this->updateUsers($file);
        }
        $offset = self::USERS_PAGE_COUNT * ($curPage - 1);
        $userResult = $user->getAll(self::USERS_PAGE_COUNT, $offset, $orderBy, $orderDirection);

        $pageCount = ceil($userResult['usersCount'] / self::USERS_PAGE_COUNT);

        $pageQueryString = "&orderBy=$orderBy&orderDirection=$orderDirection";

        $this->render(
            'index',
            [
                'error' => $error,
                'uploadInfo' => $uploadInfo,
                'curPage' => $curPage,
                'perPage' => self::USERS_PAGE_COUNT,
                'pageCount' => $pageCount,
                'orderFields' => User::ORDER_FIELDS_AVAILABLE,
                'orderBy' => $orderBy,
                'orderDirection' => strtoupper($orderDirection),
                'pageQueryString' => $pageQueryString,
                ...$userResult,
            ]
        );
    }

    /**
     * Parse file and update info users to DB
     *
     * @param array $file
     *
     * @return array
     */
    private function updateUsers(array $file): array
    {
        $user = new User();
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'Ошибка загрузки файла', 'info' => null];
        }

        if (!in_array($file['type'], ['text/xml', 'text/csv'])) {
            return ['error' => 'Файл должен быть формата csv или xml', 'info' => null];
        }

        $fileContent = file_get_contents($file['tmp_name']);
        if ($file['type'] === 'text/xml') {
            @['user' => $userList] = json_decode(json_encode(simplexml_load_string($fileContent)), true);
        } else {
            $userLineList = str_getcsv($fileContent, PHP_EOL);
            $userList = [];
            $userKeys = str_getcsv(array_shift($userLineList));
            foreach ($userLineList as $userLine) {
                $userInfoList = str_getcsv($userLine);
                $userData = [];
                foreach ($userInfoList as $key => $userInfo) {
                    $userData[$userKeys[$key]] = $userInfo;
                }
                $userList[] = $userData;
            }
        }

        return ['error' => null, 'info' => $user->insertOrUpdate($userList)];
    }
}