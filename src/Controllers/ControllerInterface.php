<?php

namespace Controllers;

/**
 * Controller interface
 *
 * Defines the contract that all controllers must implement in the application.
 * Controllers are responsible for handling HTTP requests, executing business logic,
 * and rendering responses.
 *
 * @package Controllers
 */
interface ControllerInterface
{
    /**
     * Main controller execution method
     *
     * Contains the core logic for handling the request, including:
     * - Authentication and authorization checks
     * - Data retrieval and processing
     * - View rendering or redirects
     *
     * @return void
     */
    function control();

    /**
     * Determines if this controller supports the given route and HTTP method
     *
     * Used by the router to match incoming requests to the appropriate controller.
     * Controllers should return true only for routes and methods they handle.
     *
     * @param string $chemin The requested route path (e.g., '/login', '/dashboard')
     * @param string $method The HTTP method (e.g., 'GET', 'POST', 'PUT', 'DELETE')
     * @return bool True if this controller handles the request, false otherwise
     */
    static function support(string $chemin, string $method): bool;
}