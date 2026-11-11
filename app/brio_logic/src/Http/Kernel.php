<?php
declare(strict_types=1);

namespace Brio\Http;

use Brio\Domain\Exceptions\AuthException;
use Brio\Domain\Exceptions\NotFoundException;
use Brio\Domain\Exceptions\RateLimitException;
use Brio\Domain\Exceptions\ValidationException;
use Brio\Infrastructure\Logging\Logger;

/** Entry point for /api/*, /admin/* and /setup requests coming from public/index.php. */
final class Kernel
{
    public static function handles(string $path): bool
    {
        return (bool)preg_match('#^(api|admin|setup)(/|$)#', $path);
    }

    public static function handle(string $path): void
    {
        // Never print PHP notices into HTML/JSON responses; log them instead.
        ini_set('display_errors', '0');
        set_error_handler(static function (int $no, string $msg, string $file, int $line): bool {
            Logger::warning('PHP notice', ['message' => $msg, 'at' => "$file:$line"]);
            return true;
        });

        $request = Request::capture($path);
        Session::start();
        $router = new Router();
        (require BRIO_ROOT . '/routes/api.php')($router);
        (require BRIO_ROOT . '/routes/admin.php')($router);

        try {
            if (!$router->dispatch($request)) {
                $request->wantsJson() ? Response::error('Endpoint not found.', 404) : Response::view('admin/error', ['message' => 'Page not found.'], 404);
            }
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), 422, $e->errors);
        } catch (RateLimitException $e) {
            Response::error($e->getMessage(), 429);
        } catch (NotFoundException $e) {
            Response::error($e->getMessage(), 404);
        } catch (AuthException $e) {
            Response::error($e->getMessage(), 401);
        } catch (\Throwable $e) {
            Logger::error('Unhandled exception', ['path' => $path, 'error' => $e->getMessage(), 'at' => $e->getFile() . ':' . $e->getLine()]);
            $request->wantsJson() ? Response::error('Something went wrong. Check storage/logs for details.', 500)
                                  : Response::view('admin/error', ['message' => 'Something went wrong. Check app/brio_logic/storage/logs for details.'], 500);
        }
    }
}
