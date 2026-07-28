import {
  Image,
  ImageBackground,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from "react-native";

type HeaderField = {
  label: string;
  value: string;
};

type AppHeaderProps = {
  backgroundImageUri: string;
  logoUri: string;
  title: string;
  actionIconUris: string[];
  fields: HeaderField[];
  badgeImageUri: string;
  badgeTextTop: string;
  badgeTextBottom: string;
  onActionPress?: (index: number) => void;
};

export default function AppHeader({
  backgroundImageUri,
  logoUri,
  title,
  actionIconUris,
  fields,
  badgeImageUri,
  badgeTextTop,
  badgeTextBottom,
  onActionPress,
}: AppHeaderProps) {
  return (
    <View style={styles.wrapper}>
      <ImageBackground
        source={{ uri: backgroundImageUri }}
        resizeMode="cover"
        style={styles.backgroundImage}
      >
        <View style={styles.topRow}>
          <Image
            source={{ uri: logoUri }}
            resizeMode="contain"
            style={styles.logoImage}
          />
          <Text style={styles.headerTitle}>{title}</Text>
          <View style={styles.spacer} />
          {actionIconUris.map((iconUri, index) => (
            <TouchableOpacity
              key={iconUri + index}
              style={styles.actionIconButton}
              activeOpacity={0.8}
              onPress={() => onActionPress?.(index)}
            >
              <Image
                source={{ uri: iconUri }}
                resizeMode="contain"
                style={styles.actionIcon}
              />
            </TouchableOpacity>
          ))}
        </View>

        <View style={styles.profileCard}>
          <View style={styles.labelColumn}>
            {fields.map((field) => (
              <Text
                key={field.label}
                style={styles.labelText}
              >{`${field.label}`}</Text>
            ))}
          </View>
          <View style={styles.valueColumn}>
            {fields.map((field) => (
              <Text
                key={field.label}
                style={styles.valueText}
              >{`: ${field.value}`}</Text>
            ))}
          </View>
          <View style={styles.spacer} />
          <ImageBackground
            source={{ uri: badgeImageUri }}
            resizeMode="stretch"
            style={styles.dayStartBadge}
          >
            <Text style={styles.badgeTextTop}>{badgeTextTop}</Text>
            <Text style={styles.badgeTextBottom}>{badgeTextBottom}</Text>
          </ImageBackground>
        </View>
      </ImageBackground>
    </View>
  );
}

const styles = StyleSheet.create({
  wrapper: {
    width: "100%",
  },
  backgroundImage: {
    paddingTop: 24,
    paddingHorizontal: 16,
    paddingBottom: 24,
  },
  topRow: {
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 20,
  },
  logoImage: {
    width: 28,
    height: 28,
    marginRight: 8,
  },
  headerTitle: {
    color: "#FFFFFF",
    fontSize: 18,
    fontWeight: "bold",
  },
  spacer: {
    flex: 1,
  },
  actionIconButton: {
    width: 38,
    height: 38,
    borderRadius: 12,
    backgroundColor: "rgba(255,255,255,0.16)",
    alignItems: "center",
    justifyContent: "center",
    marginLeft: 10,
  },
  actionIcon: {
    width: 22,
    height: 22,
  },
  profileCard: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#FFFFFF",
    borderRadius: 12,
    paddingVertical: 16,
    paddingHorizontal: 16,
    shadowColor: "#000000",
    shadowOpacity: 0.15,
    shadowOffset: { width: 0, height: 2 },
    shadowRadius: 6,
    elevation: 4,
  },
  labelColumn: {
    marginRight: 4,
  },
  valueColumn: {
    flex: 2,
  },
  labelText: {
    color: "#5F5E5E",
    fontSize: 13,
    fontWeight: "bold",
    marginVertical: 4,
  },
  valueText: {
    color: "#000000",
    fontSize: 13,
    fontWeight: "bold",
    marginVertical: 4,
  },
  dayStartBadge: {
    width: 75,
    height: 75,
    justifyContent: "center",
    alignItems: "center",
  },
  badgeTextTop: {
    color: "#FFFFFF",
    fontSize: 11,
    fontWeight: "bold",
  },
  badgeTextBottom: {
    color: "#FFFFFF",
    fontSize: 11,
    fontWeight: "bold",
    marginTop: 2,
  },
});
