# Technical Exercise for first recruitment

Backend exercise.

PHP / Composer + BlueM/Tree

Test:

curl -X POST 'http://tova.dev/pro/api/expand_validator' --data '{"a.*.y.t": "integer", "a.*.y.u": "integer", "a.*.z": "object|keys:w,o", "b": "array", "b.c": "string", "b.d": "object", "b.d.e": "integer|min:5", "b.d.f": "string"}'