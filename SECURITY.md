# Security Policy

## Reporting a Vulnerability

Do not report security vulnerabilities through public GitHub issues,
discussions, or pull requests.

Report potential vulnerabilities to AWS Security through the
[AWS vulnerability reporting page](https://aws.amazon.com/security/vulnerability-reporting/).
Include the following information when available:

- the affected Smithy PHP version or commit
- a description of the issue and its potential impact
- steps or sample code that reproduce the issue
- any suggested mitigation

AWS Security will coordinate with the project maintainers to investigate the
report and communicate next steps.

## Supported Versions

Smithy PHP is in developer preview. Security fixes are applied to the latest
development version. The project does not currently provide security support
for older preview versions.

## Security Considerations

Smithy models and code generation settings can control generated application
behavior. Use models and configuration from trusted sources and review
generated code before deployment.

Plugins, interceptors, protocols, signers, identity resolvers, endpoint
resolvers, and HTTP clients run with the privileges of the application.
Install and register these extensions only from trusted sources.

Applications that accept endpoint, proxy, TLS, or request override
configuration from another trust boundary must validate that configuration
before constructing a client.
