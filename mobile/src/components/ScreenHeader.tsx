import { MaterialCommunityIcons } from "@expo/vector-icons";
import React from "react";
import { StyleSheet, Text, TouchableOpacity, View } from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";

type ScreenHeaderProps = {
  title: string;
  onBack?: () => void;
  rightElement?: React.ReactNode;
  bgColor?: string;
};

export default function ScreenHeader({
  title,
  onBack,
  rightElement,
  bgColor = "#7367F0",
}: ScreenHeaderProps) {
  return (
    <SafeAreaView style={{ backgroundColor: bgColor }} edges={["top", "left", "right"]}>
      <View style={styles.header}>
        {onBack ? (
          <TouchableOpacity
            style={styles.backBtn}
            onPress={onBack}
            activeOpacity={0.7}
          >
            <MaterialCommunityIcons name="arrow-left" size={24} color="#FFFFFF" />
          </TouchableOpacity>
        ) : (
          <View style={styles.spacer} />
        )}

        <Text style={styles.headerTitle} numberOfLines={1}>
          {title}
        </Text>

        {rightElement ? (
          <View style={styles.rightSlot}>{rightElement}</View>
        ) : (
          <View style={styles.spacer} />
        )}
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  header: {
    height: 56,
    paddingHorizontal: 12,
    flexDirection: "row",
    alignItems: "center",
  },
  backBtn: {
    width: 40,
    height: 40,
    alignItems: "center",
    justifyContent: "center",
  },
  spacer: {
    width: 40,
  },
  headerTitle: {
    flex: 1,
    textAlign: "center",
    color: "#FFFFFF",
    fontSize: 18,
    fontWeight: "800",
  },
  rightSlot: {
    minWidth: 40,
    alignItems: "flex-end",
    justifyContent: "center",
  },
});
