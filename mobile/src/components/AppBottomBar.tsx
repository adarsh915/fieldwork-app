import { MaterialCommunityIcons } from "@expo/vector-icons";
import { StyleSheet, Text, TouchableOpacity, View } from "react-native";

export type BottomTabKey = "Home" | "Track" | "Reward" | "Calendar";

type TabItem = {
  key: BottomTabKey;
  label: string;
  icon: keyof typeof MaterialCommunityIcons.glyphMap;
};

const TABS: TabItem[] = [
  { key: "Home", label: "Home", icon: "home-outline" },
  { key: "Track", label: "Track", icon: "crosshairs-gps" },
  { key: "Reward", label: "Reward", icon: "gift-outline" },
  { key: "Calendar", label: "Calender", icon: "calendar-month-outline" },
];

export default function AppBottomBar({
  activeKey,
  onTabPress,
}: {
  activeKey: BottomTabKey;
  onTabPress?: (key: BottomTabKey) => void;
}) {
  return (
    <View style={styles.wrapper}>
      {TABS.map((tab) => {
        const active = tab.key === activeKey;
        return (
          <TouchableOpacity
            key={tab.key}
            style={styles.tab}
            activeOpacity={0.8}
            onPress={() => onTabPress?.(tab.key)}
          >
            <MaterialCommunityIcons
              name={tab.icon}
              size={26}
              color={active ? "#7367F0" : "#A3A3A3"}
              style={styles.icon}
            />
            <Text style={[styles.label, active && styles.labelActive]}>
              {tab.label}
            </Text>
          </TouchableOpacity>
        );
      })}
    </View>
  );
}

const styles = StyleSheet.create({
  wrapper: {
    position: "absolute",
    bottom: 0,
    left: 0,
    right: 0,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-around",
    paddingVertical: 10,
    backgroundColor: "#FFFFFF",
    borderTopWidth: 1,
    borderTopColor: "#E8E8E8",
  },
  tab: {
    flex: 1,
    alignItems: "center",
  },
  icon: {
    marginBottom: 4,
  },
  label: {
    fontSize: 10,
    fontWeight: "700",
    color: "#A3A3A3",
  },
  labelActive: {
    color: "#7367F0",
  },
});
