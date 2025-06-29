package config

// MVP: Comment out complex auto-flag generation for now
// We'll use simple manual flags for the MVP and can restore this later

/*
import (
	"fmt"
	"reflect"
	"strconv"
	"strings"

	"github.com/spf13/cobra"
	"github.com/spf13/viper"
)

// AutoFlags automatically generates CLI flags from struct tags using reflection.
// This eliminates the need to manually synchronize flags, config, and defaults.
type AutoFlags struct {
	cmd   *cobra.Command
	viper *viper.Viper
}

// NewAutoFlags creates a new auto-flag generator.
func NewAutoFlags(cmd *cobra.Command, v *viper.Viper) *AutoFlags {
	return &AutoFlags{
		cmd:   cmd,
		viper: v,
	}
}

// BindFlags automatically generates CLI flags from struct tags.
// Supports tags: flag, usage, default
// Example: `flag:"namespace,n" usage:"PHP namespace" default:"Generated"`
func (af *AutoFlags) BindFlags(configStruct interface{}) error {
	return af.bindFlags(reflect.ValueOf(configStruct), reflect.TypeOf(configStruct), "")
}

// bindFlags recursively processes struct fields and generates flags.
func (af *AutoFlags) bindFlags(v reflect.Value, t reflect.Type, prefix string) error {
	// Handle pointer to struct
	if t.Kind() == reflect.Ptr {
		if v.IsNil() {
			return fmt.Errorf("config struct pointer is nil")
		}
		return af.bindFlags(v.Elem(), t.Elem(), prefix)
	}

	// Only process structs
	if t.Kind() != reflect.Struct {
		return fmt.Errorf("expected struct, got %s", t.Kind())
	}

	// Process each field
	for i := 0; i < t.NumField(); i++ {
		field := t.Field(i)
		fieldValue := v.Field(i)

		// Skip unexported fields
		if !field.IsExported() {
			continue
		}

		// Handle embedded structs (like PHP PHPConfig in GenerateConfig)
		if field.Anonymous {
			if err := af.bindFlags(fieldValue, field.Type, prefix); err != nil {
				return fmt.Errorf("failed to bind embedded struct %s: %w", field.Name, err)
			}
			continue
		}

		// Handle nested structs
		if field.Type.Kind() == reflect.Struct {
			nestedPrefix := af.buildPrefix(prefix, field)
			if err := af.bindFlags(fieldValue, field.Type, nestedPrefix); err != nil {
				return fmt.Errorf("failed to bind nested struct %s: %w", field.Name, err)
			}
			continue
		}

		// Generate flag for this field
		if err := af.bindField(field, fieldValue, prefix); err != nil {
			return fmt.Errorf("failed to bind field %s: %w", field.Name, err)
		}
	}

	return nil
}

// buildPrefix builds the config key prefix for nested structs.
func (af *AutoFlags) buildPrefix(parentPrefix string, field reflect.StructField) string {
	mapstructureTag := field.Tag.Get("mapstructure")
	if mapstructureTag != "" && mapstructureTag != "-" {
		if parentPrefix != "" {
			return parentPrefix + "." + mapstructureTag
		}
		return mapstructureTag
	}

	// Fall back to lowercase field name
	fieldName := strings.ToLower(field.Name)
	if parentPrefix != "" {
		return parentPrefix + "." + fieldName
	}
	return fieldName
}

// bindField creates a CLI flag for a single struct field.
func (af *AutoFlags) bindField(field reflect.StructField, fieldValue reflect.Value, prefix string) error {
	flagTag := field.Tag.Get("flag")

	// Skip fields without flag tag or with flag:"-"
	if flagTag == "" || flagTag == "-" {
		return nil
	}

	// Parse flag tag: "flag-name" or "flag-name,short"
	parts := strings.Split(flagTag, ",")
	flagName := strings.TrimSpace(parts[0])
	var shortFlag string
	if len(parts) > 1 {
		shortFlag = strings.TrimSpace(parts[1])
	}

	// Get usage and default from tags
	usage := field.Tag.Get("usage")
	defaultValue := field.Tag.Get("default")

	// Build config key for viper binding
	configKey := af.buildConfigKey(field, prefix)

	// Set default in viper if specified
	if defaultValue != "" {
		if err := af.setViperDefault(configKey, defaultValue, field.Type); err != nil {
			return fmt.Errorf("failed to set default for %s: %w", flagName, err)
		}
	}

	// Create flag based on field type
	if err := af.createFlag(flagName, shortFlag, usage, field.Type, configKey); err != nil {
		return fmt.Errorf("failed to create flag %s: %w", flagName, err)
	}

	return nil
}

// buildConfigKey builds the viper config key for a field.
func (af *AutoFlags) buildConfigKey(field reflect.StructField, prefix string) string {
	mapstructureTag := field.Tag.Get("mapstructure")
	if mapstructureTag != "" && mapstructureTag != "-" {
		if prefix != "" {
			return prefix + "." + mapstructureTag
		}
		return mapstructureTag
	}

	// Fall back to lowercase field name
	fieldName := strings.ToLower(field.Name)
	if prefix != "" {
		return prefix + "." + fieldName
	}
	return fieldName
}

// setViperDefault sets a default value in viper with proper type conversion.
func (af *AutoFlags) setViperDefault(key, defaultValue string, fieldType reflect.Type) error {
	switch fieldType.Kind() {
	case reflect.String:
		af.viper.SetDefault(key, defaultValue)
	case reflect.Bool:
		if val, err := strconv.ParseBool(defaultValue); err == nil {
			af.viper.SetDefault(key, val)
		} else {
			return fmt.Errorf("invalid bool default %q", defaultValue)
		}
	case reflect.Int, reflect.Int32, reflect.Int64:
		if val, err := strconv.ParseInt(defaultValue, 10, 64); err == nil {
			af.viper.SetDefault(key, val)
		} else {
			return fmt.Errorf("invalid int default %q", defaultValue)
		}
	case reflect.Float32, reflect.Float64:
		if val, err := strconv.ParseFloat(defaultValue, 64); err == nil {
			af.viper.SetDefault(key, val)
		} else {
			return fmt.Errorf("invalid float default %q", defaultValue)
		}
	default:
		return fmt.Errorf("unsupported type for default: %s", fieldType.Kind())
	}
	return nil
}

// createFlag creates the appropriate cobra flag and binds it to viper.
func (af *AutoFlags) createFlag(flagName, shortFlag, usage string, fieldType reflect.Type, configKey string) error {
	flags := af.cmd.Flags()

	switch fieldType.Kind() {
	case reflect.String:
		if shortFlag != "" {
			flags.StringP(flagName, shortFlag, "", usage)
		} else {
			flags.String(flagName, "", usage)
		}
	case reflect.Bool:
		if shortFlag != "" {
			flags.BoolP(flagName, shortFlag, false, usage)
		} else {
			flags.Bool(flagName, false, usage)
		}
	case reflect.Int, reflect.Int32, reflect.Int64:
		if shortFlag != "" {
			flags.IntP(flagName, shortFlag, 0, usage)
		} else {
			flags.Int(flagName, 0, usage)
		}
	case reflect.Float32, reflect.Float64:
		if shortFlag != "" {
			flags.Float64P(flagName, shortFlag, 0.0, usage)
		} else {
			flags.Float64(flagName, 0.0, usage)
		}
	default:
		return fmt.Errorf("unsupported flag type: %s", fieldType.Kind())
	}

	// Bind flag to viper
	return af.viper.BindPFlag(configKey, flags.Lookup(flagName))
}
*/
