<?php

namespace App\Http\Controllers\AdminTools;

use App\Exceptions\Logging\HandlerLoggingInterface;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AdminToolsExceptionController extends Controller
{
    public function exceptionselect(Request $request): View
    {
        return view('admin.tools.exception.select', [
            'exceptions' => [
                'TokenMismatchException' => 'TokenMismatchException',
                'InternalServerError'    => 'InternalServerError',
                'DiscordException'       => 'DiscordException',
            ],
        ]);
    }

    /**
     * @throws TokenMismatchException
     * @throws Exception
     */
    public function exceptionselectsubmit(Request $request): array
    {
        switch ($request->get('exception')) {
            case 'TokenMismatchException':
                throw new TokenMismatchException(__('controller.admintools.flash.exception'));
            case 'InternalServerError':
                throw new Exception(__('controller.admintools.flash.exception'));
            case 'DiscordException':
                Log::error('Manual Generic test log from web');

                Log::channel('discord')->error('Manual Discord test log from web');

                Log::stack([
                    'daily',
                    'discord',
                ])->error('Manual stack log test from web');

                $handlerLogging = app()->make(HandlerLoggingInterface::class);
                $handlerLogging->uncaughtException(
                    $request->ip(),
                    $request->url(),
                    null,
                    null,
                    null,
                    'DiscordException',
                    'Structured logging test from web',
                );

                return [
                    'logging.default' => Config::get('logging.default'),
                    'stack_channels'  => Config::get('logging.channels.stack.channels'),
                    'discord_config'  => Config::get('logging.channels.discord'),
                ];
        }

        return [];
    }
}
