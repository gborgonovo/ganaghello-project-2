<?php

use App\Mcp\Servers\BigLogServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp', BigLogServer::class)
    ->middleware(['auth:sanctum', 'abilities:mcp:leggi', 'throttle:mcp']);
