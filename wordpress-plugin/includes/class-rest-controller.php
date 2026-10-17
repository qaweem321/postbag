<?php

/**
 * POST /wp-json/postbag/v1/subscribe
 */

namespace Postbag_Signup;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined('ABSPATH') || exit;

/**
 * The public endpoint the form submits to. It is open to visitors on purpose (no nonce, so the
 * form keeps working on cached pages); the honeypot, timing check and per-IP limit stop abuse.
 */
class Rest_Controller
{
    /**
     * Signup handler.
     *
     * @var Signup
     */
    private $signup;

    /**
     * Constructor.
     *
     * @param  Signup  $signup  Signup handler.
     */
    public function __construct(Signup $signup)
    {
        $this->signup = $signup;
    }

    /**
     * Registers the route.
     */
    public function register_hooks(): void
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Route definition.
     */
    public function register_routes(): void
    {
        register_rest_route(
            'postbag/v1',
            '/subscribe',
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'subscribe'],
                'permission_callback' => '__return_true',
                'args' => [
                    'email' => [
                        'type' => 'string',
                        'required' => true,
                    ],
                    'name' => ['type' => 'string'],
                    'list' => ['type' => 'string'],
                    'website' => ['type' => 'string'],
                    'started' => ['type' => 'integer'],
                ],
            ]
        );
    }

    /**
     * Handles a submission.
     *
     * @param  WP_REST_Request  $request  Request.
     */
    public function subscribe(WP_REST_Request $request): WP_REST_Response
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        $result = $this->signup->handle($request->get_params(), $ip);

        if (is_wp_error($result)) {
            $data = $result->get_error_data();
            $status = is_array($data) && isset($data['status']) ? (int) $data['status'] : 502;

            return new WP_REST_Response(['message' => $result->get_error_message()], $status);
        }

        return new WP_REST_Response(['message' => $result], 200);
    }
}
