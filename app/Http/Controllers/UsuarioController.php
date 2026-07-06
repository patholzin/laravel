<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    private static array $usuarios = [
        [
            'id' => 1,
            'nombre' => 'Juan Perez',
            'correo' => 'juan@gmail.com',
            'edad' => 23,
        ],
        [
            'id' => 2,
            'nombre' => 'Maria Lopez',
            'correo' => 'maria@gmail.com',
            'edad' => 28,
        ],
    ];

    // Devuelve todos los usuarios registrados en el arreglo.
    public function index()
    {
        return response()->json(self::$usuarios);
    }

    // Busca y devuelve un usuario por su identificador.
    public function show($id)
    {
        foreach (self::$usuarios as $usuario) {
            if ($usuario['id'] === (int) $id) {
                return response()->json($usuario);
            }
        }

        return response()->json([
            'mensaje' => 'Usuario no encontrado',
        ], 404);
    }

    // Valida la informacion recibida y agrega un nuevo usuario al arreglo.
    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'min:3', 'max:100'],
            'correo' => ['required', 'email'],
            'edad' => ['required', 'integer', 'gt:0'],
        ]);

        $nuevoUsuario = [
            'id' => $this->generarNuevoId(),
            'nombre' => $datos['nombre'],
            'correo' => $datos['correo'],
            'edad' => (int) $datos['edad'],
        ];

        self::$usuarios[] = $nuevoUsuario;

        return response()->json([
            'mensaje' => 'Usuario agregado correctamente',
            'usuario' => $nuevoUsuario,
        ], 201);
    }

    // Valida la informacion recibida y actualiza nombre, correo y edad.
    public function update(Request $request, $id)
    {
        foreach (self::$usuarios as $indice => $usuario) {
            if ($usuario['id'] === (int) $id) {
                $datos = $request->validate([
                    'nombre' => ['required', 'string', 'min:3', 'max:100'],
                    'correo' => ['required', 'email'],
                    'edad' => ['required', 'integer', 'gt:0'],
                ]);

                self::$usuarios[$indice]['nombre'] = $datos['nombre'];
                self::$usuarios[$indice]['correo'] = $datos['correo'];
                self::$usuarios[$indice]['edad'] = (int) $datos['edad'];

                return response()->json([
                    'mensaje' => 'Usuario actualizado correctamente',
                    'usuario' => self::$usuarios[$indice],
                ]);
            }
        }

        return response()->json([
            'mensaje' => 'Usuario no encontrado',
        ], 404);
    }

    // Elimina un usuario del arreglo por su identificador.
    public function destroy($id)
    {
        foreach (self::$usuarios as $indice => $usuario) {
            if ($usuario['id'] === (int) $id) {
                unset(self::$usuarios[$indice]);
                self::$usuarios = array_values(self::$usuarios);

                return response()->json([
                    'mensaje' => 'Usuario eliminado correctamente',
                ]);
            }
        }

        return response()->json([
            'mensaje' => 'Usuario no encontrado',
        ], 404);
    }

    private function generarNuevoId(): int
    {
        if (empty(self::$usuarios)) {
            return 1;
        }

        return max(array_column(self::$usuarios, 'id')) + 1;
    }
}
