<?php
/**
 * Created by PhpStorm.
 * User: daniel
 * Date: 10.10.14
 * Time: 14:59
 */

namespace Cundd\PersistentObjectStore\Server\ValueObject;

use Cundd\PersistentObjectStore\Server\Exception\InvalidRequestActionException;
use Cundd\PersistentObjectStore\Utility\GeneralUtility;
use React\Http\Request;

/**
 * Factory for RequestInfo instances
 *
 * @package Cundd\PersistentObjectStore\Server\ValueObject
 */
class RequestInfoFactory
{
    /**
     * Map of paths to their RequestInfo objects
     *
     * @var array
     */
    protected static $pathToRequestInfoMap = array();

    /**
     * Builds a RequestInfo instance for the given path
     *
     * @param Request $request
     * @return RequestInfo
     */
    public static function buildRequestInfoFromRequest(Request $request)
    {
        $requestInfoIdentifier = sha1(sprintf('%s-%s-%s', $request->getMethod(), $request->getPath(),
            json_encode($request->getQuery())));
        if (!isset(static::$pathToRequestInfoMap[$requestInfoIdentifier])) {
            $pathParts           = explode('/', $request->getPath());
            $pathParts           = array_values(array_filter($pathParts, function ($item) {
                return !!$item;
            }));
            $dataIdentifier      = null;
            $databaseIdentifier  = null;
            $controllerClassName = null;

            if (count($pathParts) >= 2) {
                $dataIdentifier = $pathParts[1];
            }
            if (count($pathParts) >= 1) {
                $databaseIdentifier = $pathParts[0];
            }
            $handlerAction = static::getHandlerActionForRequest($request);
            if ($databaseIdentifier && $databaseIdentifier[0] === '_') {
                $databaseIdentifier = '';
            }
            if ($dataIdentifier && $dataIdentifier[0] === '_') {
                $dataIdentifier = '';
            }

            $controllerAndActionArray = static::getControllerAndActionForRequest($request);
            if ($controllerAndActionArray) {
                list($controllerClassName, $handlerAction) = $controllerAndActionArray;

                $databaseIdentifier = isset($pathParts[2]) ? $pathParts[2] : null;
                $dataIdentifier     = isset($pathParts[3]) ? $pathParts[3] : null;
            }
            static::$pathToRequestInfoMap[$requestInfoIdentifier] =
                new RequestInfo(
                    $request,
                    $dataIdentifier,
                    $databaseIdentifier,
                    $request->getMethod(),
                    $handlerAction,
                    $controllerClassName
                );
        }
        return static::$pathToRequestInfoMap[$requestInfoIdentifier];
    }

    /**
     * Returns the handler class if the path contains a special information identifier, otherwise the Handler interface
     * name
     *
     * @param Request $request
     * @return string
     */
    public static function getHandlerClassForRequest($request)
    {
        $default = 'Cundd\\PersistentObjectStore\\Server\\Handler\\HandlerInterface';
        $path    = $request->getPath();
        if (!$path) {
            return $default;
        }
        if ($path[0] === '/') {
            $path = substr($path, 1);
        }
        if ($path[0] !== '_') {
            return $default;
        }

        $handlerIdentifier = strstr($path, '/', true);
        if ($handlerIdentifier === false) {
            $handlerIdentifier = $path;
        }
        $handlerIdentifier = substr($handlerIdentifier, 1);

        // Generate the Application name
        $applicationName = str_replace(' ', '\\', ucwords(str_replace('_', ' ', $handlerIdentifier))) . '\\Application';
        if (class_exists($applicationName)) {
            return $applicationName;
        }

        // Generate the Handler name
        $handlerName = sprintf(
            'Cundd\\PersistentObjectStore\\Server\\Handler\\%sHandler',
            ucfirst($handlerIdentifier)
        );
        if (class_exists($handlerName)) {
            return $handlerName;
        }
        return $default;
    }

    /**
     * Get the controller class and action name
     *
     * Returns the controller class and action name as array if the path contains a special information identifier. If
     * no special information identifier is given, or the controller class does not exist false is returned.
     *
     * @param Request $request
     * @return array|boolean
     */
    public static function getControllerAndActionForRequest($request)
    {
        $path = $request->getPath();
        if (!$path) {
            return false;
        }
        if ($path[0] === '/') {
            $path = substr($path, 1);
        }
        if ($path[0] !== '_') {
            return false;
        }
        if (strpos($path, '-') === false) {
            return false;
        }

        $pathParts = explode('/', substr($path, 1));
        if (count($pathParts) < 2) {
            return false;
        }
        list($controllerIdentifier, $actionIdentifier) = $pathParts;

        // Generate the Controller class name
        $controllerClassName = $controllerIdentifier;
        $controllerClassName = str_replace(' ', '', ucwords(str_replace('_', ' ', $controllerClassName)));
        $lastUnderscore      = strrpos($controllerClassName, '-');
        $controllerClassName = str_replace(' ', '\\', ucwords(str_replace('-', ' ', $controllerClassName)));
        $controllerClassName = ''
            . substr($controllerClassName, 0, $lastUnderscore + 1)
            . 'Controller\\'
            . ucfirst(substr($controllerClassName, $lastUnderscore + 1))
            . 'Controller';
        if (!class_exists($controllerClassName)) {
            return false;
        }


        $method     = $request->getMethod();
        $actionName = GeneralUtility::underscoreToCamelCase(strtolower($method) . '_' . $actionIdentifier) . 'Action';
        if (!ctype_alnum($actionName)) {
            throw new InvalidRequestActionException('Action name must be alphanumeric', 1420547305);
        }

        // Don't check if the action exists here
        // if (!method_exists($controllerClassName, $actionName)) return false;

        return array($controllerClassName, $actionName);
    }

    /**
     * Returns the handler action if the path contains a special information identifier, otherwise FALSE
     *
     * @param Request $request
     * @return string|bool
     */
    public static function getHandlerActionForRequest($request)
    {
        return static::getActionForRequestAndClass(
            $request,
            self::getHandlerClassForRequest($request)
        );

    }

    /**
     * Returns an action method name if the path contains a special information identifier, otherwise FALSE
     *
     * @param Request $request
     * @param string  $interface
     * @return string|bool
     */
    protected static function getActionForRequestAndClass($request, $interface)
    {
        $path   = $request->getPath();
        $method = $request->getMethod();
        if (!$path) {
            return false;
        }
        if ($path[0] === '/') {
            $path = substr($path, 1);
        }
        $pathParts = explode('/', $path);

        foreach ($pathParts as $currentPathPart) {
            if ($currentPathPart && $currentPathPart[0] === '_') {
                $handlerAction = GeneralUtility::underscoreToCamelCase(
                        strtolower($method) . '_' . substr($currentPathPart, 1)
                    ) . 'Action';
                if (method_exists($interface, $handlerAction)) {
                    return $handlerAction;
                }
            }
        }
        return false;
    }

    /**
     * Returns the special server action if the path contains a special information identifier, otherwise FALSE
     *
     * @param Request $request
     * @return string|bool
     */
    public static function getServerActionForRequest($request)
    {
        $path   = $request->getPath();
        $method = $request->getMethod();
        if ($path[0] === '/') {
            $path = substr($path, 1);
        }
        if ($path[0] !== '_' || $method !== 'POST') {
            return false;
        }
        list($action,) = explode('/', substr($path, 1), 2);

        if (in_array($action, array('shutdown', 'restart',))) {
            return $action;
        }
        return false;
    }

    /**
     * Creates a copy of the given Request Info with the given body
     *
     * @param RequestInfo $requestInfo
     * @param mixed       $body
     * @return RequestInfo
     */
    public static function copyWithBody($requestInfo, $body)
    {
        return new RequestInfo(
            $requestInfo->getRequest(),
            $requestInfo->getDataIdentifier(),
            $requestInfo->getDatabaseIdentifier(),
            $requestInfo->getMethod(),
            $requestInfo->getAction(),
            $requestInfo->getControllerClass(),
            $body
        );

    }
} 