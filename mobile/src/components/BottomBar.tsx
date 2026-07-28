import { Image, StyleSheet, Text, TouchableOpacity, View } from "react-native";

type TabItem = {
  label: string;
  iconUri: string;
  active?: boolean;
};

type BottomBarProps = {
  items: TabItem[];
  onTabPress?: (label: string) => void;
};

export default function BottomBar({ items, onTabPress }: BottomBarProps) {
  return (
    <View style={styles.bottomNav}>
      {items.map((item) => (
        <TouchableOpacity
          key={item.label}
          style={styles.tabItem}
          onPress={() => onTabPress?.(item.label)}
          activeOpacity={0.8}
        >
          <Image
            source={{ uri: item.iconUri }}
            resizeMode="contain"
            style={styles.tabIcon}
          />
          <Text style={[styles.tabLabel, item.active && styles.tabLabelActive]}>
            {item.label}
          </Text>
        </TouchableOpacity>
      ))}
    </View>
  );
}

const styles = StyleSheet.create({
  bottomNav: {
    position: "absolute",
    bottom: 0,
    left: 0,
    right: 0,
    flexDirection: "row",
    justifyContent: "space-around",
    alignItems: "center",
    backgroundColor: "#FFFFFF",
    paddingVertical: 8,
    borderTopWidth: 1,
    borderTopColor: "#E0E0E0",
  },
  tabItem: {
    alignItems: "center",
    flex: 1,
  },
  tabIcon: {
    width: 26,
    height: 26,
    marginBottom: 4,
  },
  tabLabel: {
    color: "#000000",
    fontSize: 10,
    fontWeight: "bold",
    textAlign: "center",
  },
  tabLabelActive: {
    color: "#7c3aed",
  },
});
