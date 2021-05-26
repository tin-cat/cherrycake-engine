<?php

const VALIDATE_USERNAME_INSTAGRAM = 0; // Value must be a valid Instagram username
const VALIDATE_USERNAME_TWITTER = 1; // Value must be a valid Twitter username

const VALIDATE_EMAIL_METHOD_SIMPLE = 0; // Most simple method of validating an email, just checking its syntax.
const VALIDATE_EMAIL_METHOD_MAILGUN = 1; // Advanced method using Mailgun third party.
const VALIDATE_EMAIL_METHOD_MAILBOXLAYER = 2; // Advanced method using Mailboxlayer third party.

const VALIDATE_PASSWORD_STRENGTH_WEAKNESS_TOO_SHORT = 0;
const VALIDATE_PASSWORD_STRENGTH_WEAKNESS_AT_LEAST_ONE_NUMBER = 1;
const VALIDATE_PASSWORD_STRENGTH_WEAKNESS_AT_LEAST_ONE_LETTER = 2;
const VALIDATE_PASSWORD_STRENGTH_WEAKNESS_UPPERCASE_AND_LOWERCASE = 3;
const VALIDATE_PASSWORD_STRENGTH_WEAKNESS_MATCHES_USERNAME = 4;
