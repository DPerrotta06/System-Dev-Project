<?php

namespace App\Controllers;
//TESTING FOR NOW WILL NOT BE HARDCODED DURING PRODUCTION
$menus = [
    'main' =>
    [
        'entree' => ['Canapé', 'Antipasto'],
        'station_froide' => [
            'Bar à pains',
            'Focaccia',
            'Viandes froides assorties',
            'Fromages assortis',
            'Mozzarella et tomates',
            'Olives assorties'
        ],
        'station_chaude' => [
            'Mini burger',
            'Mini arancini',
            'Ailes de poulet',
            'Calmars frits'
        ],
        'pates' => ['Handkerchiefs', 'Manicotti', 'Penne'],
        'salade' => ['Mixte', 'César'],
        'plat_principal' => [
            'Boeuf braisé',
            'Filet Mignon',
            'Poitrine de poulet',
            'Saumon'
        ],
        'dessert' => ['Dessert'],
        'bar' => ['Vin blanc', 'Vin rouge', 'Bar ouvert']
    ],
    'buffet' =>
    [
        'hot_meats' => [
            'Italian Sausage',
            'Chorizo Sausage',
            'Loukanica Sausage',
            'Cacciatore Sausage',
            'Mini Pork Ribs',
            'Mini Burgers',
            'Mini Meatballs',
            'Chicken Skewers',
            'Beef Skewers',
            'Chicken Wings',
            'General Tao',
            'Orange Beef',
            'Mini Arangini',
            'Mini Quiche',
            'Braised Trippe'
        ],
        'cold_antipasto' => ['Assorted Coldcuts', 'Prosciutto on Morza', 'Assorted Cheeses', 'Mozzarina & Tomatoes', 'Assorted Tapenade', 'Assorted Olives', 'Grilled Vegetables', 'Canapés du Chef (+ $6.00)', 'Crudités & Dip'],
        'hot_fish' => ['Fried Calamari', 'Stuffed Mussels', 'Mussels Marinara', 'Mussels Creamy Pesto', 'Won Ton Shrimp'],
        'cold_fish' => ['Smoked Salmon', 'Salmon Gravlax', 'Salmon Tartare', 'Crab Salad'],
        'options' => ['Flambée Shrimp (+ $7.00)'],
        'hot_vegetarian' => [],
        'included' => []
    ],
    'midnight_table' => []
];
return $view->render('client_form.html.twig', [
    'menu' => $menu
]);
