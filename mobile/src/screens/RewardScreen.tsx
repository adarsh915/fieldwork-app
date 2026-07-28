import { MaterialCommunityIcons } from "@expo/vector-icons";
import { useNavigation } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import { StyleSheet, Text, View } from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";

import AppBottomBar from "../components/AppBottomBar";
import type { RootStackParamList } from "../navigation/RootNavigator";

type Nav = NativeStackNavigationProp<RootStackParamList, "Reward">;

export default function RewardScreen() {
  const navigation = useNavigation<Nav>();

  return (
    <SafeAreaView style={styles.safe}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Reward</Text>
      </View>

      <View style={styles.body}>
        <MaterialCommunityIcons name="gift-outline" size={54} color="#7367F0" />
        <Text style={styles.text}>
          Reward screen (UI will be added from your design)
        </Text>
      </View>

      <AppBottomBar
        activeKey="Reward"
        onTabPress={(key) => {
          if (key === "Home") navigation.replace("Dashboard");
          if (key === "Track") navigation.replace("Track");
          if (key === "Reward") return;
          if (key === "Calendar") navigation.replace("Calendar");
        }}
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: "#EDEDED" },
  header: {
    backgroundColor: "#7367F0",
    paddingVertical: 18,
    alignItems: "center",
  },
  headerTitle: {
    color: "#FFFFFF",
    fontSize: 18,
    fontWeight: "800",
  },
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
