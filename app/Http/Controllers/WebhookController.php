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
            $lines = explode('\\n', $commit['message']);

            $embeds[] = [
                'title'       => substr(array_shift($lines), 0, 256),
                'description' => substr(trim(view('app.commit.commit', [
                    'commit' => $commit,
                    'lines'  => $lines,
                ])->render()), 0, 2000),
                'url'         => $commit['url'],
            ];

            if (!empty($commit['added'])) {
                $embeds[] = [
                    'color'       => 2328118, // #238636
                    'description' => substr(trim(view('app.commit.added', [
                        'commit' => $commit,
                    ])->render()), 0, 2000)
                ];
            }

            if (!empty($commit['modified'])) {
                $embeds[] = [
                    'color'       => 25284, // #0062C4
                    'description' => substr(trim(view('app.commit.modified', [
                        'commit' => $commit,
                    ])->render()), 0, 2000)
                ];
            }

            if (!empty($commit['removed'])) {
                $embeds[] = [
                    'color'       => 14300723, // #DA3633
                    'description' => substr(trim(view('app.commit.removed', [
                        'commit' => $commit,
                    ])->render()), 0, 2000)
                ];
            }

            $lastKey = array_key_last($embeds);
            $embeds[$lastKey]['timestamp'] = $commit['timestamp'];
        }

        // Add footer to the last embed
        $lastKey = array_key_last($embeds);
        $embeds[$lastKey]['footer'] = [
            'icon_url' => 'https://keystone.guru/images/external/discord/footer_image.png',
            'text'     => 'Keystone.guru Discord Bot'
        ];

        $discordApiService->sendEmbeds(env('DISCORD_GITHUB_WEBHOOK'), $embeds);

        return response()->noContent();
    }
}
