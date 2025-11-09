<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Parser2 API Documentation",
 *     description="Advanced parsing, processing, normalization, and categorization of data from multiple sources",
 *
 *     @OA\Contact(
 *         email="support@parser2.local"
 *     ),
 *
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Parser2 API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Laravel Sanctum authentication token"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication and authorization endpoints"
 * )
 * @OA\Tag(
 *     name="Profile",
 *     description="User profile management endpoints"
 * )
 * @OA\Tag(
 *     name="Settings",
 *     description="User settings and preferences"
 * )
 * @OA\Tag(
 *     name="System",
 *     description="System health and status endpoints"
 * )
 * @OA\Tag(
 *     name="Parsers",
 *     description="Parser management and execution endpoints"
 * )
 */
abstract class Controller
{
    //
}
