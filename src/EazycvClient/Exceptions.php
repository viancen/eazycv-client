<?php

/**
 * Base exception for all EazyCV client errors.
 */
class Eazycv_Error extends Exception
{
}

/**
 * HTTP-layer error raised by the EazyCV client.
 */
class Eazycv_HttpError extends Eazycv_Error
{
}

/**
 * The parameters passed to the API call are invalid or not provided when required.
 */
class Eazycv_ValidationError extends Eazycv_Error
{
}

/**
 * The provided API key is not a valid EazyCV API key.
 */
class Eazycv_Invalid_Key extends Eazycv_Error
{
}
