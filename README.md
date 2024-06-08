when updating laravel version:

 - FluentModel get/set
 - ValidationExceptionWithMessages can go
 - MethodCallingRule comes back
 - HasTraits booting/initialize of traits
 - getForeignKey -> getForeignKeyName for BelongsTo
 - WithJson can use Resources


# Laravel Support Package

This library adds a bunch of support functionality to core Laravel. Tries to be roughly analogous to
the huge set of Rails functionality, but not strictly.

## Eloquent Support

### Autosaving Relations

### Model Naming Helpers

### Nested Attributes

### Transactional Database Writes

### Translation Support

### Validation

## Request/Response Support

### Strong Parameters

One of the main problems with guarded attributes on models is that different parts of the code often have different
rules for what attributes are fillable or not, eg. user controller vs admin controller vs background job, etc.

Strong parameters is a way of filtering input (usually on an individual controller level) and passing that to an
unguarded model. That way, each individual controller can decide what attributes it's allowed to modify, and in the case
of jobs, etc, permit modifying of all of them.

Individual models still may have guards on them, for global protection, but by default, they are all unguarded.

### AutoResponds

### Conditional Gets

## Routing Support

### REST-based URL generation

