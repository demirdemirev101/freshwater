<?php
 
namespace App\Http\Middleware;
 
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
 
class CorsMiddleware
{
    private function allowedOrigins(): array
    {
        $configuredOrigins = preg_split(
            '/\s*,\s*/',
            (string) env('FRONTEND_URLS', ''),
            -1,
            PREG_SPLIT_NO_EMPTY
        ) ?: [];

        return array_values(array_unique(array_merge([
            'http://localhost:3000',
            'http://127.0.0.1:3000',
            'http://192.168.1.103:3000',
            'http://freshwater.test:3000',
            'http://app.freshwater.test:3000',
        ], $configuredOrigins)));
    }
 
    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->headers->get('Origin');

        $allowedOrigins = $this->allowedOrigins();
        $allowedOrigin = in_array($origin, $allowedOrigins) ? $origin : null;

        $headers = [
            'Access-Control-Allow-Origin'      => $allowedOrigin,
            'Access-Control-Allow-Methods'     => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            'Access-Control-Allow-Headers'     => 'Accept, Authorization, Content-Type, Origin, Referer, X-CSRF-TOKEN, X-Requested-With, X-XSRF-TOKEN',
            'Access-Control-Allow-Credentials' => 'true',
        ];

        if ($request->isMethod('OPTIONS')) {
            return response('', 200)->withHeaders($headers);
        }

        $response = $next($request);

        if ($allowedOrigin) {
            foreach ($headers as $key => $value) {
                $response->headers->set($key, $value);
            }
        }

        return $response;
    }
}
