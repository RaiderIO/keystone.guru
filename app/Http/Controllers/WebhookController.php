<?php

namespace App\Http\Controllers;

use App\Service\Discord\DiscordApiServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class WebhookController extends Controller
{

    /**
     * Validate an incoming github webhook
     *
     * @param Request $request
     *
     * @return void
     * @throws BadRequestException|UnauthorizedException
     * @see https://dev.to/ryan1/how-to-validate-github-webhooks-with-laravel-and-php-2he1
     */
    protected function validateGithubWebhook(Request $request)
    {
        if (($signature = $request->headers->get('X-Hub-Signature')) == null) {
            throw new BadRequestException('Header not set');
        }

        $signatureParts = explode('=', $signature);

        if (count($signatureParts) != 2) {
            throw new BadRequestException('signature has invalid format');
        }

        $knownSignature = hash_hmac('sha1', $request->getContent(), env('GITHUB_WEBHOOK_SECRET'));

        if (!hash_equals($knownSignature, $signatureParts[1])) {
            throw new UnauthorizedException('Could not verify request signature ' . $signatureParts[1]);
        }
    }

    /**
     * @param Request $request
     * @param DiscordApiServiceInterface $discordApiService
     * @return Response
     */
    public function github(Request $request, DiscordApiServiceInterface $discordApiService)
    {
        $this->validateGithubWebhook($request);

        $commits = $request->get('commits');

        $embeds = [];
        foreach ($commits as $commit) {
            $lines = explode('\n', $commit['message']);

            $embed = [
                'color'       => 14641434, // '#DF691A'
                'title'       => substr(array_shift($lines), 0, 256),
                'description' => substr(trim(view('app.commit.github', [
                    'commit' => $commit,
                    'lines'  => $lines,
                ])->render()), 0, 2000),
                'url'         => $commit['url'],
                'timestamp'   => $commit['timestamp'],
                'footer'      => [
                    'icon_url' => 'https://keystone.guru/images/external/discord/footer_image.png',
                    'text'     => 'Keystone.guru Discord Bot'
                ],
            ];

            $embeds[] = $embed;
        }

        $discordApiService->sendEmbeds(env('DISCORD_GITHUB_WEBHOOK'), $embeds);

        return response()->noContent();
    }
}
