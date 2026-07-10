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
            'password' => 'admin123',
            'rol' => 'admin',
            'token' => 'token-admin-123',
        ],
        [
            'id' => 2,
            'nombre' => 'Maria Lopez',
            'correo' => 'maria@gmail.com',
            'edad' => 28,
            'password' => 'usuario123',
            'rol' => 'usuario',
            'token' => 'token-usuario-123',
        ],
    ];

    // Valida las credenciales del usuario y devuelve un token temporal.
    public function login(Request $request)
    {
        $datos = $request->validate([
            'correo' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        foreach (self::$usuarios as $usuario) {
            if ($usuario['correo'] === $datos['correo'] && $usuario['password'] === $datos['password']) {
                return response()->json([
                    'mensaje' => 'Inicio de sesion correcto',
                    'token' => $usuario['token'],
                    'usuario' => $this->ocultarDatosPrivados($usuario),
                ]);
            }
        }

        return response()->json([
            'mensaje' => 'Credenciales incorrectas',
        ], 401);
    }

    // Devuelve todos los usuarios registrados en el arreglo.
    public function index(Request $request)
    {
        $usuarioAutenticado = $this->obtenerUsuarioAutenticado($request);

        if (! $usuarioAutenticado) {
            return $this->respuestaNoAutorizado();
        }

        return response()->json(array_map(function ($usuario) {
            return $this->ocultarDatosPrivados($usuario);
        }, self::$usuarios));
    }

    // Busca y devuelve un usuario por su identificador.
    public function show(Request $request, $id)
    {
        $usuarioAutenticado = $this->obtenerUsuarioAutenticado($request);

        if (! $usuarioAutenticado) {
            return $this->respuestaNoAutorizado();
        }

        foreach (self::$usuarios as $usuario) {
            if ($usuario['id'] === (int) $id) {
                return response()->json($this->ocultarDatosPrivados($usuario));
            }
        }

        return response()->json([
            'mensaje' => 'Usuario no encontrado',
        ], 404);
    }

    // Valida la informacion recibida y agrega un nuevo usuario al arreglo.
    public function store(Request $request)
    {
        if (! $this->esAdministrador($request)) {
            return $this->respuestaSoloAdmin();
        }

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
            'password' => 'usuario123',
            'rol' => 'usuario',
            'token' => 'token-usuario-' . $this->generarNuevoId(),
        ];

        self::$usuarios[] = $nuevoUsuario;

        return response()->json([
            'mensaje' => 'Usuario agregado correctamente',
            'usuario' => $this->ocultarDatosPrivados($nuevoUsuario),
        ], 201);
    }

    // Valida la informacion recibida y actualiza nombre, correo y edad.
    public function update(Request $request, $id)
    {
        if (! $this->esAdministrador($request)) {
            return $this->respuestaSoloAdmin();
        }

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
                    'usuario' => $this->ocultarDatosPrivados(self::$usuarios[$indice]),
                ]);
            }
        }

        return response()->json([
            'mensaje' => 'Usuario no encontrado',
        ], 404);
    }

    // Elimina un usuario del arreglo por su identificador.
    public function destroy(Request $request, $id)
    {
        if (! $this->esAdministrador($request)) {
            return $this->respuestaSoloAdmin();
        }

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

    private function obtenerUsuarioAutenticado(Request $request): ?array
    {
        $token = $request->bearerToken();

        foreach (self::$usuarios as $usuario) {
            if ($usuario['token'] === $token) {
                return $usuario;
            }
        }

        return null;
    }

    private function esAdministrador(Request $request): bool
    {
        $usuario = $this->obtenerUsuarioAutenticado($request);

        return $usuario && $usuario['rol'] === 'admin';
    }

    private function ocultarDatosPrivados(array $usuario): array
    {
        unset($usuario['password'], $usuario['token']);

        return $usuario;
    }

    private function respuestaNoAutorizado()
    {
        return response()->json([
            'mensaje' => 'Token no proporcionado o invalido',
        ], 401);
    }

    private function respuestaSoloAdmin()
    {
        return response()->json([
            'mensaje' => 'No tienes permisos de administrador',
        ], 403);
    }
}
