# Serde Optimization Status

**Status:** Current as of September 30, 2026

This document records serde optimizations present in this repository and
follow-up work that still requires validation.

## Completed Work

The current serde paths include:

- provider-owned JSON, XML, CBOR, form, and HTTP binding schema extensions
- generated direct-property serialization
- positional output construction
- optimized generic CBOR deserialization

## Remaining Work

A direct-buffer XML writer is an unvalidated optimization candidate. It
requires a complete replacement for the `XMLWriter` state machine and a
focused benchmark before adoption.

## Related Documents

- [Serialization and Deserialization](serialization-and-deserialization.md)
- [Codec Architecture](../codec/serialization-codecs.md)
- [Schema Extensions](../core/schema-extensions.md)
- [XML Serde Follow-ups](../codec/xml-followups.md)
