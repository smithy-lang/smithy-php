$version: "2"

namespace smithy.example.petstore

use smithy.protocols#rpcv2Cbor

/// A small service for managing pets available through a local store.
@title("Pet Store")
@rpcv2Cbor
service PetStore {
    version: "2026-10-09"
    resources: [Pet]
}

/// A pet managed by the store.
resource Pet {
    identifiers: {
        petId: PetId
    }
    create: CreatePet
    read: GetPet
    update: UpdatePet
    delete: DeletePet
    list: ListPets
}

/// Add a pet to the store.
operation CreatePet {
    input := {
        @required
        @notProperty
        pet: NewPet
    }
    output := {
        @required
        @notProperty
        pet: PetRecord
    }
    errors: [PetConflictError, ValidationError]
}

/// Retrieve a pet by ID.
@readonly
operation GetPet {
    input := {
        @required
        @resourceIdentifier("petId")
        petId: PetId
    }
    output := {
        @required
        @notProperty
        pet: PetRecord
    }
    errors: [PetNotFoundError, ValidationError]
}

/// Change the mutable details of a pet.
@idempotent
operation UpdatePet {
    input := {
        @required
        @resourceIdentifier("petId")
        petId: PetId

        @required
        @notProperty
        pet: PetUpdate
    }
    output := {
        @required
        @notProperty
        pet: PetRecord
    }
    errors: [PetNotFoundError, ValidationError]
}

/// Remove a pet from the store.
@idempotent
operation DeletePet {
    input := {
        @required
        @resourceIdentifier("petId")
        petId: PetId
    }
    errors: [PetNotFoundError, ValidationError]
}

/// List pets in stable ID order.
@readonly
@paginated(
    inputToken: "nextToken",
    outputToken: "nextToken",
    pageSize: "pageSize",
    items: "pets"
)
operation ListPets {
    input := {
        nextToken: String

        @range(min: 1, max: 25)
        pageSize: Integer
    }
    output := {
        @required
        pets: PetRecords

        nextToken: String
    }
    errors: [ValidationError]
}

/// Information supplied when adding a pet.
structure NewPet {
    @required
    name: PetName

    @required
    species: PetSpecies

    ageYears: Integer
    description: String
    photo: Blob
    traits: PetTraits
    attributes: PetAttributes
    vaccinations: VaccinationRecords
    details: PetDetails
    metrics: PetMetrics
}

/// Fields that can be changed after a pet has been added.
structure PetUpdate {
    name: PetName
    status: PetStatus
    ageYears: Integer
    description: String
    photo: Blob
    traits: PetTraits
    attributes: PetAttributes
    vaccinations: VaccinationRecords
    details: PetDetails
    metrics: PetMetrics
}

/// The complete stored representation of a pet.
structure PetRecord {
    @required
    petId: PetId

    @required
    name: PetName

    @required
    species: PetSpecies

    @required
    status: PetStatus

    ageYears: Integer
    description: String
    photo: Blob
    traits: PetTraits
    attributes: PetAttributes
    vaccinations: VaccinationRecords
    details: PetDetails
    metrics: PetMetrics

    @required
    createdAt: Timestamp

    @required
    updatedAt: Timestamp
}

/// Less-common scalar values kept together to keep the main resource readable.
structure PetMetrics {
    @default(false)
    houseTrained: Boolean

    @default(5)
    temperamentScore: Byte

    kennelNumber: Short
    eventSequence: Long
    weightKilograms: Float
    latitude: Double
    registrySequence: BigInteger
    adoptionFee: BigDecimal
    medicalPriority: MedicalPriority
    measurements: SparseMeasurements
}

structure VaccinationRecord {
    @required
    name: String

    administeredAt: Timestamp
}

structure DogDetails {
    @required
    breed: String

    favoriteToy: String
}

structure CatDetails {
    indoorOnly: Boolean
}

structure BirdDetails {
    canTalk: Boolean
}

structure OtherDetails {
    notes: PetTraits
}

union PetDetails {
    dog: DogDetails
    cat: CatDetails
    bird: BirdDetails
    other: OtherDetails
}

enum PetSpecies {
    DOG = "dog"
    CAT = "cat"
    BIRD = "bird"
    OTHER = "other"
}

enum PetStatus {
    AVAILABLE = "available"
    PENDING = "pending"
    ADOPTED = "adopted"
}

intEnum MedicalPriority {
    ROUTINE = 1
    SOON = 2
    URGENT = 3
}

@length(min: 1, max: 64)
string PetName

@pattern("^pet-[0-9]+$")
string PetId

list PetRecords {
    member: PetRecord
}

list PetTraits {
    member: String
}

@sparse
list VaccinationRecords {
    member: VaccinationRecord
}

map PetAttributes {
    key: String
    value: String
}

@sparse
map SparseMeasurements {
    key: String
    value: Double
}

@error("client")
structure PetNotFoundError {
    @required
    message: String

    @required
    petId: PetId
}

@error("client")
structure PetConflictError {
    @required
    message: String
}

@error("client")
structure ValidationError {
    @required
    message: String

    field: String
}
