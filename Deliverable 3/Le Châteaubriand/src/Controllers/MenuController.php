<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RedBeanPHP\R;
use Twig\Environment;

class MenuController
{
    public function __construct(
        private Environment $twig,
        private string $basePath,
    ) {}

    private function requireAuth(Response $response): ?Response
    {
        if (!($_SESSION['authenticated'] ?? false)) {
            return $response->withHeader('Location', $this->basePath . '/auth')->withStatus(302);
        }

        return null;
    }

    private function getMenuByMenuId(int $menuId): ?array
    {
        $menu = R::getRow(
            'SELECT * FROM menu WHERE menuId = ?',
            [$menuId]
        );

        return $menu ?: null;
    }

    // GET /menus
    // List all menus with their items.
    public function index(Request $request, Response $response): Response
    {
        if ($redirect = $this->requireAuth($response)) {
            return $redirect;
        }

        $menus = R::getAll('SELECT * FROM v_menu_items ORDER BY menuName, categoryName, itemName');

        $grouped = [];
        foreach ($menus as $row) {
            $menuId = (int) $row['menuId'];

            if (!isset($grouped[$menuId])) {
                $grouped[$menuId] = [
                    'menuId'         => $menuId,
                    'menuName'       => $row['menuName'],
                    'pricePerPerson' => $row['pricePerPerson'],
                    'items'          => [],
                ];
            }

            $grouped[$menuId]['items'][] = [
                'itemId'       => $row['itemId'],
                'itemName'     => $row['itemName'],
                'itemPrice'    => $row['itemPrice'],
                'extraPrice'   => $row['extraPrice'],
                'categoryName' => $row['categoryName'],
            ];
        }

        $html = $this->twig->render('menu_index.html.twig', [
            'menus'     => array_values($grouped),
            'base_path' => $this->basePath,
            'app_lang'  => $_SESSION['lang'] ?? 'en',
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    // GET /menus/{id}
    // Single menu detail with all food items.
    public function show(Request $request, Response $response, array $args): Response
    {
        if ($redirect = $this->requireAuth($response)) {
            return $redirect;
        }

        $menuId = (int) $args['id'];

        $rows = R::getAll(
            'SELECT * FROM v_menu_items WHERE menuId = ? ORDER BY categoryName, itemName',
            [$menuId]
        );

        if (empty($rows)) {
            $response->getBody()->write('Menu not found.');
            return $response->withStatus(404);
        }

        $menu = [
            'menuId'         => $rows[0]['menuId'],
            'menuName'       => $rows[0]['menuName'],
            'pricePerPerson' => $rows[0]['pricePerPerson'],
            'items'          => array_map(fn($row) => [
                'itemId'       => $row['itemId'],
                'itemName'     => $row['itemName'],
                'itemPrice'    => $row['itemPrice'],
                'extraPrice'   => $row['extraPrice'],
                'categoryName' => $row['categoryName'],
            ], $rows),
        ];

        $html = $this->twig->render('menu_index.html.twig', [
            'menus'     => [$menu],
            'base_path' => $this->basePath,
            'app_lang'  => $_SESSION['lang'] ?? 'en',
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    // GET /menus/{id}/edit
    // Edit a menu's name, price per person, and linked food items.
    public function edit(Request $request, Response $response, array $args): Response
    {
        if ($redirect = $this->requireAuth($response)) {
            return $redirect;
        }

        $menuId = (int) $args['id'];
        $menu = $this->getMenuByMenuId($menuId);

        if (!$menu) {
            $response->getBody()->write('Menu not found.');
            return $response->withStatus(404);
        }

        $allItems = R::getAll(
            'SELECT fi.*, fc.categoryName
             FROM foodItem fi
             JOIN foodCategory fc ON fc.categoryId = fi.categoryId
             ORDER BY fc.categoryName, fi.itemName'
        );

        $linkedIds = R::getCol(
            'SELECT itemId FROM menuFoodItem WHERE menuId = ?',
            [$menuId]
        );

        $html = $this->twig->render('menu_index.html.twig', [
            'menu'      => $menu,
            'allItems'  => $allItems,
            'linkedIds' => $linkedIds,
            'base_path' => $this->basePath,
            'app_lang'  => $_SESSION['lang'] ?? 'en',
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    // POST /menus/{id}/edit
    // Save menu edits and re-sync food item links using menuId.
    public function update(Request $request, Response $response, array $args): Response
    {
        if ($redirect = $this->requireAuth($response)) {
            return $redirect;
        }

        $menuId = (int) $args['id'];
        $menu = $this->getMenuByMenuId($menuId);

        if (!$menu) {
            $response->getBody()->write('Menu not found.');
            return $response->withStatus(404);
        }

        $data = (array) $request->getParsedBody();

        $menuName = trim((string) ($data['menuName'] ?? $menu['menuName']));
        $pricePerPerson = (float) ($data['pricePerPerson'] ?? $menu['pricePerPerson']);

        R::exec(
            'UPDATE menu SET menuName = ?, pricePerPerson = ? WHERE menuId = ?',
            [$menuName, $pricePerPerson, $menuId]
        );

        R::exec('DELETE FROM menuFoodItem WHERE menuId = ?', [$menuId]);

        foreach ((array) ($data['itemIds'] ?? []) as $itemId) {
            R::exec(
                'INSERT INTO menuFoodItem (menuId, itemId) VALUES (?, ?)',
                [$menuId, (int) $itemId]
            );
        }

        return $response
            ->withHeader('Location', $this->basePath . '/menus/' . $menuId)
            ->withStatus(302);
    }
}
