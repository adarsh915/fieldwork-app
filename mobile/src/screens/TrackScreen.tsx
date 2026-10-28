import { MaterialIcons } from "@expo/vector-icons";
import { useNavigation } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import { StyleSheet, Text, View } from "react-native";

import ScreenHeader from "../components/ScreenHeader";
import type { RootStackParamList } from "../navigation/RootNavigator";

type Nav = NativeStackNavigationProp<RootStackParamList, "Track">;

export default function TrackScreen() {
  const navigation = useNavigation<Nav>();

  return (
    <View style={styles.safe}>
      <ScreenHeader
        title="Track"
        onBack={() => navigation.goBack()}
      />

      <View style={styles.body}>
        <MaterialIcons name="location-searching" size={52} color="#7367F0" />
        <Text style={styles.text}>
          Track screen (UI will be added from your design)
        </Text>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: "#EDEDED" },
  body: {
    flex: 1,
    alignItems: "center",
    justifyContent: "center",
    padding: 16,
  },
  text: {
    marginTop: 12,
    fontWeight: "800",
    color: "#111827",
  },
});
