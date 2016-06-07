<?php

class Eazycv_Error extends Exception {}
class Eazycv_HttpError extends Eazycv_Error {}

/**
 * The parameters passed to the API call are invalid or not provided when required
 */
class Eazycv_ValidationError extends Eazycv_Error {}

/**
 * The provided API key is not a valid Eazycv API key
 */
class Eazycv_Invalid_Key extends Eazycv_Error {}
