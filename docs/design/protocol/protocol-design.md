# Protocol Design

**Status:** Current as of October 12, 2026

**Packages:** `smithy-php`, `aws-sdk-php`

## Summary

Protocols convert an operation and input shape into a transport request, then
convert a transport response into an output shape or service exception.
HTTP protocols retain PSR-7 messages inside HTTP-specific transport envelopes.

All protocol implementations satisfy `ProtocolInterface`:

```php
interface ProtocolInterface
{
    public function getId(): string;

    public function getMessageExchange(): MessageExchangeInterface;

    public function serializeRequest(
        OperationInterface $operation,
        SerializableStructInterface $input
    ): TransportRequestInterface;

    public function setServiceEndpoint(
        TransportRequestInterface $request,
        Endpoint $endpoint
    ): TransportRequestInterface;

    public function deserializeResponse(
        OperationInterface $operation,
        TransportResponseInterface $response
    ): SerializableStructInterface;
}
```

Operations provide their input, output, error, and operation schemas.
Generated operations may additionally provide the exception class used for
unmodeled service errors. Protocols use codecs for body encoding. A protocol's
`MessageExchangeInterface` declares the concrete request and response envelope
types it shares with a compatible transport.

## Protocol Resolution

Generated and dynamic clients use
`Smithy\Client\Protocol\ProtocolResolverInterface`. A client config may begin
without a selected protocol. Once the client attaches its runtime
`ServiceInterface`, the resolver compares registered protocol IDs with the
service schema's protocol traits and selects the first applicable
implementation in preference order.

Callers can override automatic selection with either a protocol implementation
or a modeled protocol shape ID. `smithy-php` registers RPC v2 CBOR by default;
provider runtimes add their own implementations to the same resolver. The
selected protocol, all registrations, and the protocols applicable to a
service are available through `ClientConfig`.

## Ownership

| Concern | Owner |
|---|---|
| Body format | Codec |
| URI, headers, status, and error envelope | Protocol |
| Smithy HTTP member traits | HTTP binding layer |
| Endpoint selection | Client pipeline |
| Signing and retries | Client pipeline |
| Generic message transfer and retry preparation | Transport |
| HTTP network transfer | HTTP client through `HttpClientTransport` |

`smithy-php` provides generic HTTP binding components and RPC v2 CBOR.
`aws-sdk-php` provides AWS protocol variants and AWS error envelopes.

## HTTP Binding Protocols

`AbstractHttpBindingProtocol` implements RestJson and RestXml request and
response flow.

### Request flow

1. Resolve `HttpOperationBindingExtension` from the operation schema.
2. Resolve `HttpStructBindingExtension` from the input schema.
3. Create `HttpBindingSerializer`.
4. Serialize request-direction labels, queries, headers, payload, and body
   members.
5. Join the modeled operation path to the relative request root.
6. Set content type and content length.
7. Return an `HttpTransportRequest` containing an immutable PSR-7 request.

Serialization creates an operation-relative request once per execution. Each
attempt later calls `setServiceEndpoint()` to bind the resolved endpoint URL
and endpoint headers while preserving the operation path and query.

HTTP binding serialization supports:

| Trait | Request behavior |
|---|---|
| `@httpLabel` | Substitute a URI path label |
| `@httpQuery` | Add one named query parameter |
| `@httpQueryParams` | Add a map of query parameters |
| `@httpHeader` | Add one header |
| `@httpPrefixHeaders` | Add a map of prefixed headers |
| `@httpPayload` | Use the member as the body |
| Unbound member | Serialize through the body codec |

### Response flow

1. Route error responses to the protocol error deserializer.
2. Create the output member buffer.
3. Read response-direction headers, prefix headers, response code, and payload
   bindings.
4. Deserialize document members through the body codec.
5. Construct the output through the operation factory.

`@httpResponseCode` stores the HTTP status in one output member.

Input and output schemas can contain traits that are meaningful only in the
opposite direction. Runtime binding metadata therefore builds separate
request and response plans: request serialization ignores response-only
bindings, while response deserialization ignores request-only bindings.
Modeled request header names are validated when schema extensions are built.
Application-controlled header values and complete prefix-header names are
validated before entering trusted normalized message paths.

## RestJson

`Aws\Core\Protocol\RestJsonProtocol` combines:

- `AbstractHttpBindingProtocol`
- `JsonCodec`
- `RestJsonErrorDeserializer`
- `application/json`
- epoch-seconds as the default body timestamp format

The JSON codec honors `@jsonName`. HTTP header timestamps follow Smithy HTTP
binding defaults.

## RestXml

`Aws\Core\Protocol\RestXmlProtocol` combines:

- `AbstractHttpBindingProtocol`
- `XmlCodec`
- `RestXmlErrorDeserializer`
- `application/xml`
- date-time as the default body timestamp format

The client config can supply the service's default XML namespace.

## AWS JSON

`AwsJson1_0Protocol` and `AwsJson1_1Protocol` extend
`AbstractAwsJsonProtocol`.

Both protocols:

- send `POST /`
- use `X-Amz-Target` for operation dispatch
- serialize the complete input as JSON
- deserialize the complete output from JSON
- use `AwsJsonErrorDeserializer`
- ignore `@jsonName`

The content type distinguishes the versions:

| Protocol | Content type |
|---|---|
| AWS JSON 1.0 | `application/x-amz-json-1.0` |
| AWS JSON 1.1 | `application/x-amz-json-1.1` |

## AWS Query

`AwsQueryProtocol` extends `AbstractQueryProtocol`.

Requests are form URL encoded and include the action and service version.
Responses use XML. The XML deserializer descends through the operation result
wrapper before hydrating the output.

## EC2 Query

`Ec2QueryProtocol` uses the query base with EC2-specific form settings:

- capitalized resolved wire names
- flattened lists
- maps disabled
- date-time timestamps

Responses use the `<OperationResponse>` wrapper.

## RPC v2 CBOR

`RpcV2CborProtocol` extends `AbstractRpcV2Protocol`.

The request target is:

```text
/service/{Service}/operation/{Operation}
```

Requests and responses use:

- `Smithy-Protocol: rpc-v2-cbor`
- `Content-Type: application/cbor` when a request body is present
- `Accept: application/cbor`

Unit inputs omit the request body. Unit outputs can return an empty body.
Non-Unit success responses require HTTP 200 and the expected
`Smithy-Protocol` header.

## Errors

`HttpErrorDeserializer` defines shared modeled-error construction.
Protocol-specific subclasses extract the error discriminator and message.

| Protocol family | Error source |
|---|---|
| RestJson | JSON body and AWS error headers |
| RestXml | XML error envelope |
| AWS JSON | AWS JSON error fields and headers |
| AWS Query and EC2 Query | XML error wrapper |
| RPC v2 CBOR | RPC v2 error envelope and protocol header |

Modeled errors use the operation error schema. Unmatched service errors use
the exception class supplied by the operation.

## Generated and Dynamic Clients

Generated and dynamic clients select and call the same protocol classes
through the shared resolver.

Generated operations construct generated outputs and errors. Dynamic
operations construct document-backed outputs and errors from runtime schemas.
Protocol tests exercise both paths for non-streaming operations.

Dynamic streaming input and output remain outside the developer preview.
Generated streaming payloads use the shared HTTP binding and body-stream
contracts.

## Validation

Smithy tests cover RPC v2 CBOR, directional HTTP bindings, header validation,
error deserialization, relative endpoint binding, and URI construction. AWS
protocol suites cover AWS Query, EC2 Query, AWS JSON, RestJson, and RestXml
generated and dynamic paths.

## Related Documents

- [Protocol Testing and Validation](testing-and-validation.md)
- [Serialization and Deserialization](../serde/serialization-and-deserialization.md)
- [Codec Design](../codec/serialization-codecs.md)
- [Schema Extensions](../core/schema-extensions.md)
- [Dynamic Client Testing and Validation](../client/dynamic/testing-and-validation.md)
