<?php

namespace Paheko\Plugin\Perm;

use Paheko\UserException;
use Paheko\ValidationException;
use Paheko\Web\Router;

class ApiRouter
{
    protected string $method;
    protected string $uri;

    public function __construct($base)
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? $_SERVER['REDIRECT_REQUEST_METHOD'];
        $this->uri = substr(Router::getRequestURI(), strlen($base));

        if ($this->method === 'POST') {
            $type = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
            if (strpos($type, '/json') !== false) {
                $input = trim(file_get_contents('php://input'));
                $_POST = (array) json_decode($input, true);
            }
        }
    }

    public function er404(string $path)
    {
        throw new UserException("Cannot $this->method $path", 404);
    }

    public function get(string $route, callable ...$handlers)
    {
        $this->execRoute('GET', $route, ...$handlers);
    }

    public function post(string $route, callable ...$handlers)
    {
        $this->execRoute('POST', $route, ...$handlers);
    }

    public function put(string $route, callable ...$handlers)
    {
        $this->execRoute('PUT', $route, ...$handlers);
    }

    public function delete(string $route, callable ...$handlers)
    {
        $this->execRoute('DELETE', $route, ...$handlers);
    }

    public function patch(string $route, callable ...$handlers)
    {
        $this->execRoute('PATCH', $route, ...$handlers);
    }

    public function options(string $route, callable ...$handlers)
    {
        $this->execRoute('OPTIONS', $route, ...$handlers);
    }

    public function all(string $route, callable ...$handlers)
    {
        $this->execRoute('ALL', $route, ...$handlers);
    }

    private function execRoute(string $method, string $route, callable ...$handlers)
    {
        $params = [];
        if ($this->routeMatch($method, $route, $params)) {
            $response = null;
            $code = 200;
            foreach ($handlers as $handler) {
                try {
                    $response = $handler(...$params);
                } catch (ValidationException $err) {
                    $response = ['message' => $err->getMessage()];
                    $code = 400;
                } catch (UserException $err) {
                    $response = ['message' => $err->getMessage()];
                    $code = $err->getCode();
                } catch (\LogicException $err) {
                    $response = ['message' => $err->getMessage()];
                    $code = 401;
                }
            }
            // Send the last $handler response
            if (isset($response)) {
                $this->sendResponse($response, $code);
            }
            exit;
        }
    }

    private function routeMatch(string $method, string $route, array &$params = [])
    {
        if ($method != 'ALL' && $this->method != $method) {
            return false;
        }

        $routeParts = explode('/', trim($route, '/'));
        $uriParts = explode('/', trim($this->uri, '/'));

        foreach ($routeParts as $i => $r) {
            $u = $uriParts[$i] ?? '';
            if (preg_match('/\[\.\.\.(.*)\]/', $r, $m)) {
                $params[$m[1]] = implode('/', array_slice($uriParts, $i));
                return true;
            } else if (preg_match('/\{(.*)\}/', $r, $m)) {
                $params[$m[1]] = $u;
            } else if ($r != $u) {
                return false;
            }
        }

        return count($routeParts) == count($uriParts);
    }

    function sendResponse($response, $code = 200)
    {
        header('Content-Type: application/json');
        http_response_code($code);
        echo json_encode($response);
    }
}