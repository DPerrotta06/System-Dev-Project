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

    // GET /menus 
    // list all menus with their items.
    public function index(Request $request, Response $response): Response
    {
        if (!($_SESSION['authenticated'] ?? false)) {
            return $response->withHeader('Location', $this->basePath . '/auth')->withStatus(302);
        }

        $menus = R::getAll('SELECT * FROM v_menu_items ORDER BY menuName, categoryName, itemName');

        // Group rows into a nested structure for the template.
        $grouped = [];
        foreach ($menus as $row) {
            $mid = $row['menuId'];
            if (!isset($grouped[$mid])) {
                $grouped[$mid] = [
                    'menuId'         => $mid,
                    'menuName'       => $row['menuName'],
                    'pricePerPerson' => $row['pricePerPerson'],
                    'items'          => [],
                ];
            }
            $grouped[$mid]['items'][] = [
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
    // single menu detail with all food items.
    public function show(Request $request, Response $response, array $args): Response
    {
        if (!($_SESSION['authenticated'] ?? false)) {
            return $response->withHeader('Location', $this->basePath . '/auth')->withStatus(302);
        }

        $rows = R::getAll(
            'SELECT * FROM v_menu_items WHERE menuId = ? ORDER BY categoryName, itemName',
            [(int) $args['id']]
        );

        if (empty($rows)) {
            $response->getBody()->write('Menu not found.');
            return $response->withStatus(404);
        }

        $menu = [
            'menuId'         => $rows[0]['menuId'],
            'menuName'       => $rows[0]['menuName'],
            'pricePerPerson' => $rows[0]['pricePerPerson'],
            'items'          => array_map(fn($r) => [
                'itemId'       => $r['itemId'],
                'itemName'     => $r['itemName'],
                'itemPrice'    => $r['itemPrice'],
                'extraPrice'   => $r['extraPrice'],
                'categoryName' => $r['categoryName'],
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
    // edit a menu's name and price per person.
    public function edit(Request $request, Response $response, array $args): Response
    {
        if (!($_SESSION['authenticated'] ?? false)) {
            return $response->withHeader('Location', $this->basePath . '/auth')->withStatus(302);
        }

        $menu      = R::load('menu', (int) $args['id']);
        $allItems  = R::getAll('SELECT fi.*, fc.categoryName FROM foodItem fi JOIN foodCategory fc ON fc.categoryId = fi.categoryId ORDER BY fc.categoryName, fi.itemName');
        $linkedIds = R::getCol('SELECT itemId FROM menuFoodItem WHERE menuId = ?', [(int) $args['id']]);

        if (!$menu->id) {
            $response->getBody()->write('Menu not found.');
            return $response->withStatus(404);
        }

        $html = $this->twig->render('menu_index.html.twig', [
            'menu'      => $menu->export(),
            'allItems'  => $allItems,
            'linkedIds' => $linkedIds,
            'base_path' => $this->basePath,
            'app_lang'  => $_SESSION['lang'] ?? 'en',
        ]);
        $response->getBody()->write($html);
        return $response;
    }

    // POST /menus/{id}/edit
    // save menu edits and re-sync food item links.
    public function update(Request $request, Response $response, array $args): Response
    {
        if (!($_SESSION['authenticated'] ?? false)) {
            return $response->withHeader('Location', $this->basePath . '/auth')->withStatus(302);
        }

        $data = (array) $request->getParsedBody();
        $id   = (int) $args['id'];
        $menu = R::load('menu', $id);

        if (!$menu->id) {
            $response->getBody()->write('Menu not found.');
            return $response->withStatus(404);
        }

        $menu->menuName      = trim($data['menuName']      ?? $menu->menuName);
        $menu->pricePerPerson = (float) ($data['pricePerPerson'] ?? $menu->pricePerPerson);
        R::store($menu);

        // Re-sync the food item links.
        R::exec('DELETE FROM menuFoodItem WHERE menuId = ?', [$id]);
        foreach ((array) ($data['itemIds'] ?? []) as $itemId) {
            $link         = R::dispense('menuFoodItem');
            $link->menuId = $id;
            $link->itemId = (int) $itemId;
            R::store($link);
        }

        return $response
            ->withHeader('Location', $this->basePath . '/menus/' . $id)
            ->withStatus(302);
    }
}
