<?php

/*
|--------------------------------------------------------------------------
| Identidade visual (ficheiros em /public ou subpastas tipo public/img/…)
|--------------------------------------------------------------------------
|
| Para usar a vossa marca: coloquem os ficheiros e ajustem as variáveis no .env
| (caminhos relativos à pasta public/).
|
*/

return [

    'favicon_svg' => env('BRAND_FAVICON_SVG', 'img/gratoai.svg'),

    'favicon_ico' => env('BRAND_FAVICON_ICO', 'img/gratoai.ico'),

    /** Topo da app (navbar escura) e zonas onde hoje usa gratoai_black + invert onde aplicável */
    'logo_main' => env('BRAND_LOGO_MAIN', 'img/gratoai_black.png'),

    /** Centro das páginas guest (login, registo) — x-application-logo */
    'logo_guest' => env('BRAND_LOGO_GUEST', 'img/gratoai.png'),

];
