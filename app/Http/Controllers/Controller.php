<?php

namespace App\Http\Controllers;

/**
 * @OA\PathItem(path="/emes/api")
 * 
 * @OA\Info(
 * version="1.0.1",
 * title="Educa EMES REST Api",
 * description="Documentação da API utilizando L5 Swagger e Sanctum",
 * @OA\Contact(
 * email="emes@tjes.jus.br"
 * ),
 * @OA\License(
 * name="Apache 2.0",
 * url="http://www.apache.org/licenses/LICENSE-2.0.html"
 * )
 * )
 *
 * @OA\Server(
 * url=L5_SWAGGER_CONST_HOST,
 * description="Servidor da API"
 * )
 *
 * @OA\SecurityScheme(
 * securityScheme="bearerAuth",
 * type="http",
 * scheme="bearer",
 * bearerFormat="JWT",
 * description="Autenticação via Bearer Token (Sanctum)"
 * )
 */
abstract class Controller
{
    //
}
