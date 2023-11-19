<?php

namespace ChurchCRM\Slim\Middleware\Request;

use ChurchCRM\model\ChurchCRM\PersonQuery;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PersonAPIMiddleware
{
    public function __invoke(Request $request, Response $response, callable $next)
    {
        $personId = $request->getAttribute('route')->getArgument('personId');
        if (empty(trim($personId))) {
            return $response->withStatus(412, gettext('Missing').' PersonId');
        }

        $person = PersonQuery::create()->findPk($personId);
        if (empty($person)) {
            return $response->withStatus(412, 'PersonId : '.$personId.' '.gettext('not found'));
        }

        $request = $request->withAttribute('person', $person);

        return $next($request, $response);
    }
}
