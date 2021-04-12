<?php

/**
 * SAML 2.0 remote IdP metadata for SimpleSAMLphp.
 *
 * Remember to remove the IdPs you don't use from this file.
 *
 * See: https://simplesamlphp.org/docs/stable/simplesamlphp-reference-idp-remote
 */
$metadata['http://idp.lndo.site/simplesaml/saml2/idp/metadata.php'] = array (
  'metadata-set' => 'saml20-idp-remote',
  'entityid' => 'http://idp.lndo.site/simplesaml/saml2/idp/metadata.php',
  'SingleSignOnService' => 
  array (
    0 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      'Location' => 'http://idp.lndo.site/simplesaml/saml2/idp/SSOService.php',
    ),
  ),
  'SingleLogoutService' => 
  array (
    0 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      'Location' => 'http://idp.lndo.site/simplesaml/saml2/idp/SingleLogoutService.php',
    ),
  ),
  'certData' => 'MIIDfTCCAmWgAwIBAgIUNqCfYW484g5st8ORq+Z+BaK3zNAwDQYJKoZIhvcNAQELBQAwTjELMAkGA1UEBhMCSU4xCzAJBgNVBAgMAldCMRAwDgYDVQQHDAdLT0xLQVRBMRIwEAYDVQQKDAlsb2NhbGhvc3QxDDAKBgNVBAMMA0lEUDAeFw0yMTA0MTIxNjAwNTFaFw0zMTA0MTIxNjAwNTFaME4xCzAJBgNVBAYTAklOMQswCQYDVQQIDAJXQjEQMA4GA1UEBwwHS09MS0FUQTESMBAGA1UECgwJbG9jYWxob3N0MQwwCgYDVQQDDANJRFAwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQCs59DZqbhUrLkPveQwy63sACjHfdlNt6ym+qZE54PCKQq5sJIqJnchLc9fc+w8flRb6EGgTSxkxfp2pro91qEnjHbpHdahdm/j84kVJtlartZ8XhEb+vX2LM92LjX5hyHTYtt+gQWJERYsViQyMbfZf2BcfrUO+wQ/LiWn/kga4w+0+5V5jIYW8VQ7fWOX52iN0f5o1cc5kr7vY4Msx5RyYnzYGGAJ3PrYNZg22tCiS+rbxpqhBWws1sjzB+TFNttkwvxj24GnpV8Uqv9pMwJkTTBULDBKkY7CgSIICk9GyZtAOZ/FFrTaTT7+W48I27gjZhEG46hi1KjWKpqgf8dxAgMBAAGjUzBRMB0GA1UdDgQWBBTInpKS24Pxuxur0rfgim4J2h9hbjAfBgNVHSMEGDAWgBTInpKS24Pxuxur0rfgim4J2h9hbjAPBgNVHRMBAf8EBTADAQH/MA0GCSqGSIb3DQEBCwUAA4IBAQBXtbxPFnkM3w97TEGD+1TvbQdlOWS2xaortXZzZAm68RbOrpoX4iuLFFSrOE9HN4E75cYjWgdQYIHJB35Y7ZaS+IWP9d1Dn6eO8np6cH2ApAC6jSA2aBVks4kFROclBhilq2dRxJ6x5zc/QEnACKdJrA3GL/PhgjZncamtmZPQz0csAcNUMkQ+aRPbdYgPedMcawVm0Zwg9QKXHTQJRqQb2r6/HfTaP1+BuefnsSA5oB6JYV6SIWv76ETeGWfpcCRne2g012hu9W5YVOkLLJ+EMHygjAD83rSXMXEwPjqCiFPOEDDyrpDKp99STVpdfIm//kXW25j8qSF+EMOmxgzz',
  'NameIDFormat' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
);