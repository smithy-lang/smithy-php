# XML Serde Follow-ups

**Status:** Current as of September 30, 2026

## Length Pre-filter

smithy-java checks an XML member name's byte length before comparing its
bytes during fallback lookup.

PHP string identity already checks length before comparing string contents.
Adding `strlen($a) === strlen($b)` before `$a === $b` repeats work in the
Zend engine and is not a useful optimization for smithy-php.

## Direct-buffer Writer

`XmlSerializer` currently writes through `XMLWriter`. A PHP-owned string
buffer could reduce extension call overhead by appending precomputed element
names and namespace attributes directly.

This requires a complete writer implementation. Mixing raw tags with
`XMLWriter` calls would bypass its element stack and corrupt close-tag
tracking.

A prototype would need:

- an element stack
- separate text and attribute escaping
- namespace and empty-element handling
- the subset of `XMLWriter` operations used by `XmlSerializer`
- reset support for codec reuse

## Validation Gate

Do not replace `XMLWriter` until a prototype:

- passes the RestXml protocol fixture suite
- passes XML codec unit tests for escaping, namespaces, attributes, flattened
  collections, unions, and empty values
- preserves model-defined element order
- improves x86_64 and arm64 serialization results with OPcache and JIT enabled
- avoids a retained-memory regression across repeated codec reuse

The optimization remains deferred until those results exist.
